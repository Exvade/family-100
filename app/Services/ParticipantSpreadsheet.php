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
    public function template(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tpl');

        $options = new XlsxOptions;
        $options->setColumnWidth(42, 1);

        $writer = new XlsxWriter($options);
        $writer->openToFile($path);

        $writer->getCurrentSheet()->setName('Peserta');
        $writer->addRow(Row::fromValuesWithStyle(['Nama Peserta'], (new Style)->withFontBold(true)));

        $writer->addNewSheetAndMakeItCurrent()->setName('Petunjuk');
        $writer->addRows(array_map(fn (string $line) => Row::fromValues([$line]), [
            'Cara mengisi template',
            '1. Isi satu nama peserta per baris di kolom A pada sheet "Peserta", mulai dari baris 2.',
            '2. Jangan mengubah judul kolom di baris 1 (baris pertama otomatis dilewati).',
            '3. Nama yang sama (tanpa membedakan huruf besar/kecil) hanya dihitung satu kali.',
            '4. Maksimal '.self::MAX_ROWS.' baris dan '.self::MAX_NAME_LENGTH.' karakter per nama.',
            '5. Hanya sheet pertama yang dibaca. File .csv juga bisa dipakai (nama di kolom pertama).',
        ]));

        $writer->close();

        return $path;
    }

    /**
     * @return array{added: int, duplicates: int, invalid: int}
     *
     * @throws InvalidArgumentException bila file tidak terbaca atau melebihi batas baris
     */
    public function import(string $path, string $extension): array
    {
        // Semua-atau-tidak-sama-sekali: file yang melebihi batas atau rusak di tengah tidak meninggalkan data setengah jadi.
        return DB::transaction(fn () => $this->importRows($path, $extension));
    }

    /** @return array{added: int, duplicates: int, invalid: int} */
    private function importRows(string $path, string $extension): array
    {
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

        foreach ($this->firstColumn($path, $extension) as $line => $value) {
            $name = is_scalar($value) ? trim(preg_replace('/\s+/u', ' ', (string) $value)) : '';

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

            $known[$key] = true;
            $batch[] = ['name' => $name, 'created_at' => $now, 'updated_at' => $now];
            $added++;

            if (count($batch) >= 500) {
                $flush();
            }
        }

        $flush();

        return compact('added', 'duplicates', 'invalid');
    }

    /** @return Generator<int, mixed> nomor baris (mulai 1) => isi kolom A */
    private function firstColumn(string $path, string $extension): Generator
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

                    yield $line => ($row->cells[0] ?? null)?->getValue();
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
