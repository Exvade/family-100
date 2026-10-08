<?php

namespace App\Services;

use App\Models\Participant;
use Generator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Options as XlsxOptions;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/** Template Excel dan impor peserta doorprize dari file .xlsx / .csv (nama peserta di kolom A). */
class ParticipantSpreadsheet
{
    public const MAX_ROWS = 5000;

    private const MAX_NAME_LENGTH = 255;

    /** Isi sel A1 yang dianggap judul kolom, bukan nama peserta. */
    private const HEADERS = ['nama', 'nama peserta', 'peserta', 'name'];

    /** Tulis template ke file sementara dan kembalikan path-nya. */
    public function template(?string $defaultCategory = null): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tpl');

        $options = new XlsxOptions;
        $options->setColumnWidth(40, 1);

        $writer = new XlsxWriter($options);
        $writer->openToFile($path);

        $boldStyle = (new Style)->withFontBold(true);

        if ($defaultCategory !== null && trim($defaultCategory) !== '') {
            $catLabel = Participant::displayLabel($defaultCategory);
            $sheetName = mb_substr($catLabel, 0, 31);

            $writer->getCurrentSheet()->setName($sheetName);
            $writer->addRow(Row::fromValuesWithStyle(['Nama Peserta'], $boldStyle));

            $writer->addNewSheetAndMakeItCurrent()->setName('Petunjuk');
            $writer->addRows(array_map(fn (string $line) => Row::fromValues([$line]), [
                "Petunjuk Pengisian Template - {$catLabel}:",
                '1. Kolom A: Tulis nama peserta di sheet "'.$sheetName.'" mulai baris ke-2.',
                '2. Tidak perlu mengisi kategori, semua nama di file ini otomatis masuk ke: '.$catLabel.'.',
                '3. Jangan mengubah judul kolom di baris 1.',
                '4. Nama yang sama (tanpa membedakan huruf besar/kecil) hanya dihitung satu kali.',
                '5. Maksimal '.self::MAX_ROWS.' baris dan '.self::MAX_NAME_LENGTH.' karakter per nama.',
            ]));
        } else {
            // Template Multi-Sheet per Kategori (Tiap kategori punya sheet sendiri)
            $categories = Participant::CATEGORIES;
            $first = true;

            foreach ($categories as $cat) {
                $catLabel = Participant::displayLabel($cat);
                $sheetName = mb_substr($catLabel, 0, 31);

                if ($first) {
                    $writer->getCurrentSheet()->setName($sheetName);
                    $first = false;
                } else {
                    $writer->addNewSheetAndMakeItCurrent()->setName($sheetName);
                }

                $writer->addRow(Row::fromValuesWithStyle(['Nama Peserta'], $boldStyle));
            }

            $writer->addNewSheetAndMakeItCurrent()->setName('Petunjuk');
            $writer->addRows(array_map(fn (string $line) => Row::fromValues([$line]), [
                'Petunjuk Pengisian Template Per Kategori:',
                '1. Setiap kategori sudah memiliki Sheet tersendiri di file Excel ini:',
                '   - Sheet "Tamu Keluarga CPP"',
                '   - Sheet "Tamu Keluarga CPW"',
                '   - Sheet "Teman CPW"',
                '   - Sheet "Teman CPP"',
                '   - Sheet "Umum"',
                '2. Masukkan nama peserta di sheet kategori masing-masing mulai baris ke-2.',
                '3. Anda TIDAK PERLU mengetik nama kategori di dalam sheet, sehingga 100% bebas dari resiko salah ketik / typo.',
                '4. Nama yang sama (tanpa membedakan huruf besar/kecil) hanya dihitung satu kali.',
                '5. Maksimal '.self::MAX_ROWS.' baris dan '.self::MAX_NAME_LENGTH.' karakter per nama.',
            ]));
        }

        $writer->close();

        return $path;
    }

    /** Ekspor daftar seluruh pemenang undian ke file Excel */
    public function exportWinners(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'win');

        $options = new XlsxOptions;
        $options->setColumnWidth(8, 1);
        $options->setColumnWidth(35, 2);
        $options->setColumnWidth(25, 3);
        $options->setColumnWidth(25, 4);

        $writer = new XlsxWriter($options);
        $writer->openToFile($path);

        $writer->getCurrentSheet()->setName('Pemenang Doorprize');

        $boldStyle = (new Style)->withFontBold(true);

        $writer->addRow(Row::fromValuesWithStyle([
            'No',
            'Nama Pemenang',
            'Kategori',
            'Waktu Menang',
        ], $boldStyle));

        $winners = Participant::whereNotNull('won_at')
            ->orderBy('won_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $num = 1;
        foreach ($winners as $winner) {
            $writer->addRow(Row::fromValues([
                $num++,
                $winner->name,
                Participant::displayLabel($winner->category),
                $winner->won_at?->translatedFormat('d F Y, H:i:s') ?? '-',
            ]));
        }

        $writer->close();

        return $path;
    }

    /**
     * @return array{added: int, duplicates: int, invalid: int}
     *
     * @throws InvalidArgumentException bila file tidak terbaca atau melebihi batas baris
     */
    public function import(string $path, string $extension, ?string $defaultCategory = null): array
    {
        // Semua-atau-tidak-sama-sekali: file yang melebihi batas atau rusak di tengah tidak meninggalkan data setengah jadi.
        return DB::transaction(fn () => $this->importRows($path, $extension, $defaultCategory));
    }

    /** @return array{added: int, duplicates: int, invalid: int} */
    private function importRows(string $path, string $extension, ?string $defaultCategory = null): array
    {
        $hasExplicitCategory = $defaultCategory !== null && trim($defaultCategory) !== '';
        $defaultCat = $hasExplicitCategory ? Participant::canonicalCategory($defaultCategory) : Participant::DEFAULT_CATEGORY;

        $known = Participant::query()->pluck('name')
            ->mapWithKeys(fn (string $name) => [mb_strtolower($name) => true])
            ->all();

        $added = $duplicates = $invalid = 0;
        $batch = [];
        $now = now();

        $flush = function () use (&$batch): void {
            if ($batch !== []) {
                Participant::insertOrIgnore($batch);
                $batch = [];
            }
        };

        foreach ($this->rowValues($path, $extension) as $line => $cols) {
            $nameVal = $cols[0] ?? null;
            $catVal = $cols[1] ?? null;
            $sheetCat = $cols['sheet_category'] ?? null;
            $lineInSheet = $cols['line_in_sheet'] ?? $line;

            $name = is_scalar($nameVal) ? trim(preg_replace('/\s+/u', ' ', (string) $nameVal)) : '';

            if ($name === '') {
                continue;
            }
            if ($lineInSheet === 1 && in_array(mb_strtolower($name), self::HEADERS, true)) {
                continue;
            }
            if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
                $invalid++;

                continue;
            }

            $key = mb_strtolower($name);
            if (isset($known[$key])) {
                $duplicates++;

                continue;
            }

            // Tentukan kategori:
            // 1. Bila user memilih kategori spesifik saat upload, gunakan kategori tersebut.
            // 2. Bila sheet bernama kategori (mode multi-sheet), gunakan kategori sheet.
            // 3. Bila ada kolom B (file model lama), baca kolom B.
            // 4. Fallback ke Umum.
            if ($hasExplicitCategory) {
                $category = $defaultCat;
            } elseif ($sheetCat !== null) {
                $category = $sheetCat;
            } elseif (is_scalar($catVal) && trim((string) $catVal) !== '') {
                $category = Participant::canonicalCategory((string) $catVal);
            } else {
                $category = $defaultCat;
            }

            $known[$key] = true;
            $batch[] = [
                'name' => $name,
                'category' => $category,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $added++;

            if (count($batch) >= 500) {
                $flush();
            }
        }

        $flush();

        return compact('added', 'duplicates', 'invalid');
    }

    /** @return Generator<int, array{0: mixed, 1: mixed, sheet_category?: string|null, line_in_sheet?: int}> */
    private function rowValues(string $path, string $extension): Generator
    {
        $reader = $this->reader($extension);

        try {
            $reader->open($path);

            $line = 0;
            foreach ($reader->getSheetIterator() as $sheet) {
                $sheetName = trim($sheet->getName());
                // Lewati sheet petunjuk
                if (in_array(mb_strtolower($sheetName), ['petunjuk', 'instruction', 'instructions', 'guide'], true)) {
                    continue;
                }

                $sheetCatLower = mb_strtolower($sheetName);
                $isRecognizedCategory = in_array($sheetCatLower, [
                    'tamu keluarga cpp', 'keluarga cpp', 'tamu keluarga cpw', 'keluarga cpw',
                    'teman cpw', 'teman cpp', 'teman cpk', 'teman cpp (cpk)', 'umum', 'tamu umum'
                ], true);

                $sheetCategory = $isRecognizedCategory ? Participant::canonicalCategory($sheetName) : null;
                $lineInSheet = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $lineInSheet++;
                    $line++;
                    if ($line > self::MAX_ROWS + 50) {
                        throw new InvalidArgumentException('Jumlah baris melebihi batas '.self::MAX_ROWS.'.');
                    }

                    yield $line => [
                        0 => ($row->cells[0] ?? null)?->getValue(),
                        1 => ($row->cells[1] ?? null)?->getValue(),
                        'sheet_category' => $sheetCategory,
                        'line_in_sheet' => $lineInSheet,
                    ];
                }

                if ($extension === 'csv' || $extension === 'txt') {
                    break;
                }
            }
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new InvalidArgumentException('File tidak bisa dibaca. Pastikan formatnya .xlsx atau .csv yang valid.', 0, $e);
        } finally {
            $reader->close();
        }
    }

    private function reader(string $extension): ReaderInterface
    {
        return match (strtolower($extension)) {
            'xlsx' => new XlsxReader,
            'csv', 'txt' => new CsvReader,
            default => throw new InvalidArgumentException('Format file harus .xlsx atau .csv.'),
        };
    }
}
