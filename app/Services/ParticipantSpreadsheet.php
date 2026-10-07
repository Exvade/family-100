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
        $options->setColumnWidth(36, 1);
        $options->setColumnWidth(26, 2);

        $writer = new XlsxWriter($options);
        $writer->openToFile($path);

        $writer->getCurrentSheet()->setName('Peserta');
        $writer->addRow(Row::fromValuesWithStyle(['Nama Peserta', 'Kategori'], (new Style)->withFontBold(true)));

        $writer->addNewSheetAndMakeItCurrent()->setName('Petunjuk');
        $writer->addRows(array_map(fn (string $line) => Row::fromValues([$line]), [
            'Cara mengisi template peserta doorprize:',
            '1. Kolom A: Nama Peserta (Wajib diisi mulai baris 2).',
            '2. Kolom B: Kategori (Opsional). Pilihan kategori yang tersedia:',
            '   - Keluarga CPP',
            '   - Keluarga CPW',
            '   - Teman CPP',
            '   - Teman CPW',
            '   - UMUM',
            '3. Jika kolom Kategori dikosongkan, peserta akan dimasukkan ke kategori yang dipilih saat proses upload di web.',
            '4. Jangan mengubah judul kolom di baris 1 (baris pertama otomatis dilewati).',
            '5. Nama yang sama (tanpa membedakan huruf besar/kecil) hanya dihitung satu kali.',
            '6. Maksimal '.self::MAX_ROWS.' baris dan '.self::MAX_NAME_LENGTH.' karakter per nama.',
            '7. Hanya sheet pertama yang dibaca. File .csv juga bisa dipakai (kolom 1 nama, kolom 2 kategori).',
        ]));

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
        $defaultCat = Participant::canonicalCategory($defaultCategory);

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

            $name = is_scalar($nameVal) ? trim(preg_replace('/\s+/u', ' ', (string) $nameVal)) : '';

            if ($name === '') {
                continue;
            }
            if ($line === 1 && in_array(mb_strtolower($name), self::HEADERS, true)) {
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

            // Tentukan kategori: baca kolom B, jika kosong/invalid pakai defaultCat
            $category = is_scalar($catVal) && trim((string) $catVal) !== ''
                ? Participant::canonicalCategory((string) $catVal)
                : $defaultCat;

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

    /** @return Generator<int, array{0: mixed, 1: mixed}> nomor baris (mulai 1) => [kolom A, kolom B] */
    private function rowValues(string $path, string $extension): Generator
    {
        $reader = $this->reader($extension);

        try {
            $reader->open($path);

            foreach ($reader->getSheetIterator() as $sheet) {
                $line = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    if (++$line > self::MAX_ROWS + 1) {   // +1: baris judul
                        throw new InvalidArgumentException('Jumlah baris melebihi batas '.self::MAX_ROWS.'.');
                    }

                    yield $line => [
                        ($row->cells[0] ?? null)?->getValue(),
                        ($row->cells[1] ?? null)?->getValue(),
                    ];
                }

                break; // hanya sheet pertama
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
