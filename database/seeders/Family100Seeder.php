<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Database\Seeder;

class Family100Seeder extends Seeder
{
    public function run(): void
    {
        Answer::query()->delete();
        Question::query()->delete();

        $data = [
            [
                'question' => 'Sebutkan barang yang biasa dibawa tamu saat menghadiri pesta kondangan!',
                'display_limit' => 10,
                'answers' => [
                    ['answer' => 'Amplop / Uang', 'ranking' => 1],
                    ['answer' => 'Kado / Hadiah', 'ranking' => 2],
                    ['answer' => 'Handphone / Kamera', 'ranking' => 3],
                    ['answer' => 'Tas / Dompet', 'ranking' => 4],
                    ['answer' => 'Make Up / Lipstik', 'ranking' => 5],
                    ['answer' => 'Pasangan / Gandengan', 'ranking' => 6],
                    ['answer' => 'Undangan', 'ranking' => 7],
                    ['answer' => 'Kipas / Kipas Angin Portable', 'ranking' => 8],
                    ['answer' => 'Parfum / Minyak Wangi', 'ranking' => 9],
                    ['answer' => 'Sepatu Cadangan / Sandal', 'ranking' => 10],
                ],
            ],
            [
                'question' => 'Sebutkan hal penting yang harus dipersiapkan sebelum hari resepsi pernikahan!',
                'display_limit' => 10,
                'answers' => [
                    ['answer' => 'Gedung / Venue Pernikahan', 'ranking' => 1],
                    ['answer' => 'Katering / Menu Makanan', 'ranking' => 2],
                    ['answer' => 'Baju Pengantin / Busana', 'ranking' => 3],
                    ['answer' => 'Undangan & Souvenir', 'ranking' => 4],
                    ['answer' => 'Cincin Kawin & Mahar', 'ranking' => 5],
                    ['answer' => 'Make Up Artist (MUA)', 'ranking' => 6],
                    ['answer' => 'Dokumentasi / Fotografer', 'ranking' => 7],
                    ['answer' => 'Dekorasi Pelaminan', 'ranking' => 8],
                    ['answer' => 'MC dan Hiburan Musik', 'ranking' => 9],
                    ['answer' => 'Penghulu / Petugas KUA', 'ranking' => 10],
                ],
            ],
            [
                'question' => 'Sebutkan menu makanan pondokan yang paling cepat habis di resepsi pernikahan!',
                'display_limit' => 6,
                'answers' => [
                    ['answer' => 'Zuppa Soup', 'ranking' => 1],
                    ['answer' => 'Kambing Guling', 'ranking' => 2],
                    ['answer' => 'Es Krim / Dessert', 'ranking' => 3],
                    ['answer' => 'Dimsum / Siomay', 'ranking' => 4],
                    ['answer' => 'Sate Ayam', 'ranking' => 5],
                    ['answer' => 'Bakso', 'ranking' => 6],
                ],
            ],
            [
                'question' => 'Apa alasan utama tamu biasanya terlambat hadir di acara pernikahan?',
                'display_limit' => 6,
                'answers' => [
                    ['answer' => 'Macet di Jalan', 'ranking' => 1],
                    ['answer' => 'Dandan / Make Up Lama', 'ranking' => 2],
                    ['answer' => 'Bingung Pilih Baju (OOTD)', 'ranking' => 3],
                    ['answer' => 'Susah Cari Parkir', 'ranking' => 4],
                    ['answer' => 'Nyasar / Cari Alamat', 'ranking' => 5],
                    ['answer' => 'Bangun Kesiangan', 'ranking' => 6],
                ],
            ],
            [
                'question' => 'Selain cincin, apa barang seserahan yang paling umum diberikan untuk pengantin wanita?',
                'display_limit' => 6,
                'answers' => [
                    ['answer' => 'Perlengkapan Ibadah', 'ranking' => 1],
                    ['answer' => 'Tas dan Sepatu', 'ranking' => 2],
                    ['answer' => 'Perhiasan Emas', 'ranking' => 3],
                    ['answer' => 'Set Kosmetik / Skincare', 'ranking' => 4],
                    ['answer' => 'Baju / Pakaian', 'ranking' => 5],
                    ['answer' => 'Buah-buahan / Kue Tradisional', 'ranking' => 6],
                ],
            ],
            [
                'question' => 'Apa yang dilakukan tamu pertama kali saat sampai di gedung resepsi?',
                'display_limit' => 6,
                'answers' => [
                    ['answer' => 'Isi Buku Tamu / Beri Amplop', 'ranking' => 1],
                    ['answer' => 'Langsung Cari Makanan', 'ranking' => 2],
                    ['answer' => 'Salaman dengan Pengantin', 'ranking' => 3],
                    ['answer' => 'Foto di Photobooth', 'ranking' => 4],
                    ['answer' => 'Mencari Tempat Duduk', 'ranking' => 5],
                    ['answer' => 'Cari Teman / Reuni', 'ranking' => 6],
                ],
            ],
            [
                'question' => 'Sebutkan hal yang sering membuat pengantin merasa grogi atau lelah saat pesta pernikahan!',
                'display_limit' => 6,
                'answers' => [
                    ['answer' => 'Berdiri Terlalu Lama', 'ranking' => 1],
                    ['answer' => 'Senyum Terus Menerus', 'ranking' => 2],
                    ['answer' => 'Saat Akad / Ijab Kabul', 'ranking' => 3],
                    ['answer' => 'Baju Pengantin yang Berat', 'ranking' => 4],
                    ['answer' => 'Salaman dengan Ratusan Tamu', 'ranking' => 5],
                    ['answer' => 'Kurang Tidur / Capek', 'ranking' => 6],
                ],
            ],
        ];

        foreach ($data as $item) {
            $question = Question::updateOrCreate(
                ['question' => $item['question']],
                [
                    'display_limit' => $item['display_limit'],
                    'timer_ends_at_ms' => null,
                    'timer_remaining_ms' => null,
                    'wrong_count' => 0,
                ]
            );

            // Re-sync answers
            $question->answers()->delete();
            foreach ($item['answers'] as $ans) {
                $question->answers()->create([
                    'answer' => $ans['answer'],
                    'ranking' => $ans['ranking'],
                    'is_answered' => false,
                ]);
            }
        }
    }
}
