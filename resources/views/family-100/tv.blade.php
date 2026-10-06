<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Family 100 - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            background: #020617 url("{{ asset('images/background-quiz.png') }}") no-repeat center center;
            background-size: cover;
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #ffffff;
            user-select: none;
            position: relative;
        }

        /* Container papan jawaban tepat di dalam frame emas panggung */
        .tv-screen-container {
            position: absolute;
            top: 38.5%;
            bottom: 8.5%;
            left: 14%;
            right: 14%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            z-index: 5;
        }

        /* 1 Kolom vertikal sesuai referensi Family 100 */
        .tv-board {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: clamp(650px, 56vw, 920px);
            gap: clamp(0.35rem, 0.85vh, 0.75rem);
        }

        /* Modifikasi untuk 8 - 10 jawaban agar muat pas dan proporsional di dalam frame panggung */
        .tv-board.tv-board-dense {
            gap: clamp(0.18rem, 0.52vh, 0.42rem);
        }

        .tv-board.tv-board-dense .tv-slot {
            height: clamp(1.85rem, 3.7vh, 2.75rem);
        }

        .tv-board.tv-board-dense .tv-rank,
        .tv-board.tv-board-dense .tv-empty-badge,
        .tv-board.tv-board-dense .tv-answer-badge {
            width: clamp(1.55rem, 3.1vh, 2.25rem);
            height: clamp(1.55rem, 3.1vh, 2.25rem);
            font-size: clamp(0.85rem, 1.5vh, 1.15rem);
        }

        .tv-board.tv-board-dense .tv-answer-text {
            font-size: clamp(0.85rem, 1.35vw, 1.35rem);
            letter-spacing: 0.04em;
        }

        .tv-board.tv-board-dense .tv-card-front,
        .tv-board.tv-board-dense .tv-card-back {
            padding: 0 clamp(0.5rem, 0.9vw, 1rem);
            gap: 0.6rem;
        }

        /* Baris / Slot Jawaban Pill Capsule 3D */
        .tv-slot {
            perspective: 1000px;
            height: clamp(2.7rem, 4.8vh, 3.75rem);
            border-radius: 9999px;
            position: relative;
            cursor: default;
        }

        .tv-card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: left;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
            border-radius: 9999px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.65);
        }

        .tv-slot.revealed .tv-card-inner {
            transform: rotateX(180deg);
        }

        .tv-card-front,
        .tv-card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            padding: 0 clamp(0.75rem, 1.2vw, 1.25rem);
            gap: 1rem;
        }

        /* Sisi Depan (Belum Terbuka: baris 4 & 5 pada referensi) */
        .tv-card-front {
            background: linear-gradient(180deg, #102663 0%, #081742 50%, #040c26 100%);
            border: 2px solid rgba(212, 175, 55, 0.7);
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.25), 0 4px 10px rgba(0, 0, 0, 0.5);
            justify-content: space-between;
        }

        .tv-rank {
            flex: none;
            width: clamp(2.1rem, 3.8vh, 2.9rem);
            height: clamp(2.1rem, 3.8vh, 2.9rem);
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: radial-gradient(circle at 35% 35%, #fff1a8 0%, #d4af37 60%, #8c6a18 100%);
            color: #1a0f00;
            font-family: 'Cinzel', serif;
            font-weight: 900;
            font-size: clamp(1rem, 1.9vh, 1.4rem);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.6), inset 0 2px 2px rgba(255, 255, 255, 0.8);
            border: 1.5px solid #fff5b8;
        }

        .tv-card-front .tv-shutter {
            flex: 1;
        }

        .tv-empty-badge {
            flex: none;
            width: clamp(2.1rem, 3.8vh, 2.9rem);
            height: clamp(2.1rem, 3.8vh, 2.9rem);
            border-radius: 50%;
            background: rgba(4, 12, 38, 0.6);
            border: 1.5px solid rgba(212, 175, 55, 0.35);
        }

        /* Sisi Belakang (Terbuka: baris kuning/oranye berkilau seperti referensi) */
        .tv-card-back {
            background: linear-gradient(180deg, #f59e0b 0%, #d97706 35%, #b45309 70%, #78350f 100%);
            border: 2px solid #fde047;
            transform: rotateX(180deg);
            box-shadow: 0 0 18px rgba(245, 158, 11, 0.5), 0 4px 12px rgba(0, 0, 0, 0.6), inset 0 2px 3px rgba(255, 255, 255, 0.7);
            justify-content: space-between;
        }

        .tv-answer-text {
            flex: 1;
            font-size: clamp(1.05rem, 1.75vw, 1.75rem);
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.9), 0 0 10px rgba(0, 0, 0, 0.6);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
        }

        .tv-answer-badge {
            flex: none;
            width: clamp(2.1rem, 3.8vh, 2.9rem);
            height: clamp(2.1rem, 3.8vh, 2.9rem);
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: radial-gradient(circle at 35% 35%, #22c55e 0%, #15803d 70%, #14532d 100%);
            color: #ffffff;
            font-weight: 900;
            font-size: clamp(0.95rem, 1.7vh, 1.3rem);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.6), inset 0 2px 2px rgba(255, 255, 255, 0.7);
            border: 1.5px solid #86efac;
        }

        /* Overlay Jawaban Salah (X Merah Besar) */
        .tv-strike {
            position: fixed;
            inset: 0;
            display: grid;
            place-items: center;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(5px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
            z-index: 100;
        }

        .tv-strike.show {
            opacity: 1;
        }

        .tv-strike svg {
            width: min(55vh, 55vw);
            height: auto;
            color: #ef4444;
            filter: drop-shadow(0 0 35px #dc2626) drop-shadow(0 0 70px #b91c1c);
            animation: strikePop 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes strikePop {
            0% { transform: scale(0.4) rotate(-15deg); opacity: 0; }
            70% { transform: scale(1.1) rotate(5deg); opacity: 1; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        /* Tombol Suara */
        .tv-sound-btn {
            position: fixed;
            bottom: 2vh;
            right: 2.5vw;
            background: rgba(3, 14, 43, 0.8);
            border: 1.5px solid rgba(212, 175, 55, 0.6);
            color: #fef08a;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.45rem 1.1rem;
            border-radius: 9999px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(6px);
            transition: all 0.2s ease;
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .tv-sound-btn:hover {
            background: rgba(13, 54, 145, 0.9);
            border-color: #ffd700;
            color: #ffffff;
            transform: scale(1.04);
        }

        .tv-empty-state {
            color: rgba(255, 255, 255, 0.65);
            font-size: clamp(1.2rem, 2vw, 1.8rem);
            font-weight: 600;
            text-align: center;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Area Papan Jawaban (Tepat di dalam bingkai panggung) -->
    <main class="tv-screen-container">
        @if ($answers->isEmpty())
            <div class="tv-empty-state">Menunggu babak dimulai...</div>
        @else
            <div class="tv-board {{ $answers->count() >= 8 ? 'tv-board-dense' : '' }}">
                @foreach ($answers as $answer)
                    <div class="tv-slot {{ $answer->is_answered ? 'revealed' : '' }}" data-slot="{{ $answer->id }}">
                        <div class="tv-card-inner">
                            <!-- Sisi Depan: Tertutup (seperti baris 4 & 5 referensi) -->
                            <div class="tv-card-front">
                                <span class="tv-rank">{{ $loop->iteration }}</span>
                                <div class="tv-shutter"></div>
                                <span class="tv-empty-badge"></span>
                            </div>
                            <!-- Sisi Belakang: Terbuka (seperti baris 1, 2, 3 referensi) -->
                            <div class="tv-card-back">
                                <span class="tv-rank">{{ $loop->iteration }}</span>
                                <span class="tv-answer-text">{{ $answer->answer }}</span>
                                <span class="tv-answer-badge">✓</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </main>

    <!-- Overlay Salah (X Merah) -->
    <div class="tv-strike" data-tv-strike aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"><path d="M5 5l14 14M19 5L5 19"/></svg>
    </div>

    <!-- Tombol Suara TV -->
    <button type="button" class="tv-sound-btn" data-tv-sound title="Klik untuk mengaktifkan / mematikan suara (atau tekan X untuk tes salah, C untuk tes benar)">
        <span class="tv-sound-icon">🔊</span>
        <span class="tv-sound-text">Suara Aktif</span>
    </button>

    <script>
        const slots = document.querySelectorAll('[data-slot]');
        const stateUrl = @js(route('family-100.questions.tv.state', $question));

        async function sync() {
            try {
                const response = await fetch(stateUrl, { headers: { 'Accept': 'application/json' } });
                const { answered, wrong_count: wrongCount } = await response.json();
                let newlyRevealed = false;
                slots.forEach((slot) => {
                    const reveal = answered.includes(Number(slot.dataset.slot));
                    newlyRevealed ||= reveal && !slot.classList.contains('revealed');
                    slot.classList.toggle('revealed', reveal);
                });
                if (newlyRevealed) { playCorrect(); }
                if (wrongCount > wrongSeen) { strike(); }
                wrongSeen = wrongCount;
            } catch (error) {
                // Koneksi putus sesaat: tampilan terakhir dipertahankan
            }
        }

        // Jawaban salah: X merah besar + buzzer
        let wrongSeen = {{ $question->wrong_count }};
        const strikeEl = document.querySelector('[data-tv-strike]');
        let strikeTimeout = null;

        function strike() {
            strikeEl.classList.add('show');
            clearTimeout(strikeTimeout);
            strikeTimeout = setTimeout(() => strikeEl.classList.remove('show'), 1800);
            playWrong();
        }

        // Audio Effects Family 100
        const soundButton = document.querySelector('[data-tv-sound]');
        const soundText = soundButton.querySelector('.tv-sound-text');
        const soundIcon = soundButton.querySelector('.tv-sound-icon');
        let soundOn = true;

        const wrongAudio = new Audio('{{ asset("sounds/wrong.mp3") }}');
        wrongAudio.preload = 'auto';
        const correctAudio = new Audio('{{ asset("sounds/correct.mp3") }}');
        correctAudio.preload = 'auto';

        // Buka kunci AudioContext/Autoplay browser saat ada interaksi pertama
        function unlockAudio() {
            wrongAudio.load();
            correctAudio.load();
        }
        window.addEventListener('pointerdown', unlockAudio, { once: true });
        window.addEventListener('keydown', unlockAudio, { once: true });

        soundButton.addEventListener('click', (e) => {
            e.stopPropagation();
            soundOn = !soundOn;
            soundText.textContent = soundOn ? 'Suara Aktif' : 'Suara Mati';
            soundIcon.textContent = soundOn ? '🔊' : '🔇';
            soundButton.style.opacity = soundOn ? '1' : '0.6';
            if (soundOn) {
                playWrong();
            }
        });

        function playCorrect() {
            if (!soundOn) return;
            try {
                correctAudio.currentTime = 0;
                correctAudio.volume = 1.0;
                const p = correctAudio.play();
                if (p !== undefined) {
                    p.catch(() => {
                        const s = new Audio('{{ asset("sounds/correct.mp3") }}');
                        s.volume = 1.0;
                        s.play().catch(() => {});
                    });
                }
            } catch (err) {
                console.warn(err);
            }
        }

        function playWrong() {
            if (!soundOn) return;
            try {
                wrongAudio.currentTime = 0;
                wrongAudio.volume = 1.0;
                const p = wrongAudio.play();
                if (p !== undefined) {
                    p.catch(() => {
                        const s = new Audio('{{ asset("sounds/wrong.mp3") }}');
                        s.volume = 1.0;
                        s.play().catch(() => {});
                    });
                }
            } catch (err) {
                console.warn(err);
            }
        }

        // Shortcut keyboard testing (X: salah, C: benar)
        window.addEventListener('keydown', (e) => {
            if (e.target && ['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;
            if (e.key === 'x' || e.key === 'X') {
                strike();
            } else if (e.key === 'c' || e.key === 'C') {
                playCorrect();
            }
        });

        setInterval(sync, 1500);
    </script>
</body>
</html>

