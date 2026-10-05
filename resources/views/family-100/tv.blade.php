<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $question->question }} - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link href="{{ asset('vendor/tabler/dist/css/tabler.min.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body { min-height: 100vh; display: flex; flex-direction: column; justify-content: center; padding: 2rem; }
        .tv-question { font-size: clamp(1.75rem, 4vw, 3.5rem); font-weight: 700; text-align: center; margin-bottom: 2rem; }
        .tv-board { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; max-width: 1400px; margin: 0 auto; width: 100%; }
        .tv-slot { user-select: none; border: 2px solid var(--tblr-border-color); border-radius: .75rem; padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 1.25rem; font-size: clamp(1.25rem, 2.6vw, 2.25rem); background: var(--tblr-bg-surface); }
        .tv-slot .tv-rank { flex: none; width: 3.25rem; height: 3.25rem; border-radius: 50%; display: grid; place-items: center; background: var(--tblr-primary); color: #fff; font-weight: 700; }
        .tv-slot .tv-answer { opacity: 0; transition: opacity .4s ease; font-weight: 600; }
        .tv-slot.revealed { border-color: var(--tblr-green); }
        .tv-slot.revealed .tv-answer { opacity: 1; }
        .tv-timer { position: fixed; top: 1.5rem; right: 2rem; font-size: clamp(2rem, 5vw, 4.5rem); font-weight: 700; font-variant-numeric: tabular-nums; }
        .tv-timer.warning { color: var(--tblr-orange); }
        .tv-timer.finished { color: var(--tblr-red); animation: tv-blink 1s steps(2, start) infinite; }
        @keyframes tv-blink { to { visibility: hidden; } }
        .tv-strike { position: fixed; inset: 0; display: grid; place-items: center; background: rgba(0, 0, 0, .65); opacity: 0; pointer-events: none; transition: opacity .2s ease; z-index: 10; }
        .tv-strike.show { opacity: 1; }
        .tv-strike svg { width: min(60vh, 60vw); height: auto; color: var(--tblr-red); filter: drop-shadow(0 0 2rem rgba(214, 57, 57, .8)); }
        .tv-sound { position: fixed; bottom: 1rem; right: 1rem; opacity: .6; }
        .tv-sound:hover { opacity: 1; }
        .tv-hint { text-align: center; margin-top: 2rem; }
    </style>
</head>
<body>
    <div class="tv-timer" data-tv-timer>{{ sprintf('%02d:%02d', intdiv(intdiv($timer['remaining_ms'] + 999, 1000), 60), intdiv($timer['remaining_ms'] + 999, 1000) % 60) }}</div>

    <div class="tv-question">{{ $question->question }}</div>

    @if ($answers->isEmpty())
        <p class="text-center text-secondary fs-2">Belum ada jawaban untuk pertanyaan ini.</p>
    @else
        <div class="tv-board">
            @foreach ($answers as $answer)
                <div class="tv-slot {{ $answer->is_answered ? 'revealed' : '' }}" data-slot="{{ $answer->id }}">
                    <span class="tv-rank">{{ $loop->iteration }}</span>
                    <span class="tv-answer">{{ $answer->answer }}</span>
                </div>
            @endforeach
        </div>
        <div class="tv-hint text-secondary">
            Menampilkan {{ $answers->count() }} dari {{ $total }} jawaban.
        </div>
    @endif

    <div class="tv-strike" data-tv-strike aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M5 5l14 14M19 5L5 19"/></svg>
    </div>

    <button type="button" class="btn btn-sm tv-sound" data-tv-sound>Aktifkan suara</button>

    <script>
        const slots = document.querySelectorAll('[data-slot]');
        const stateUrl = @js(route('family-100.questions.tv.state', $question));

        async function sync() {
            try {
                const response = await fetch(stateUrl, { headers: { 'Accept': 'application/json' } });
                const { answered, timer, wrong_count: wrongCount } = await response.json();
                let newlyRevealed = false;
                slots.forEach((slot) => {
                    const reveal = answered.includes(Number(slot.dataset.slot));
                    newlyRevealed ||= reveal && !slot.classList.contains('revealed');
                    slot.classList.toggle('revealed', reveal);
                });
                if (newlyRevealed) { playCorrect(); }
                setTimer(timer);
                if (wrongCount > wrongSeen) { strike(); }
                wrongSeen = wrongCount;
            } catch (error) {
                // Koneksi putus sesaat: tampilan terakhir dipertahankan, coba lagi di polling berikutnya.
            }
        }

        // Jawaban salah: X merah besar + buzzer setiap penghitung "salah" di server bertambah.
        let wrongSeen = {{ $question->wrong_count }};
        const strikeEl = document.querySelector('[data-tv-strike]');
        let strikeTimeout = null;

        function strike() {
            strikeEl.classList.add('show');
            clearTimeout(strikeTimeout);
            strikeTimeout = setTimeout(() => strikeEl.classList.remove('show'), 1800);
            playWrong();
        }

        // Suara "ding" jawaban benar, dibuat dengan Web Audio (tanpa file audio). Browser hanya mengizinkan
        // suara setelah ada interaksi, jadi tombol di pojok kanan bawah harus diklik sekali.
        const soundButton = document.querySelector('[data-tv-sound]');
        let audio = null;
        let soundOn = false;

        soundButton.addEventListener('click', () => {
            audio ??= new (window.AudioContext || window.webkitAudioContext)();
            audio.resume();
            soundOn = !soundOn;
            soundButton.textContent = soundOn ? 'Suara aktif' : 'Suara mati';
            if (soundOn) { playCorrect(); }
        });

        function playCorrect() {
            if (!soundOn || !audio) { return; }

            // Dua nada naik (E6 lalu B6), seperti bel jawaban benar.
            [[1318.5, 0], [1975.5, 0.18]].forEach(([frequency, delay]) => {
                const start = audio.currentTime + delay;
                const oscillator = audio.createOscillator();
                const gain = audio.createGain();
                oscillator.type = 'triangle';
                oscillator.frequency.value = frequency;
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(0.5, start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.9);
                oscillator.connect(gain).connect(audio.destination);
                oscillator.start(start);
                oscillator.stop(start + 0.95);
            });
        }

        function playWrong() {
            if (!soundOn || !audio) { return; }

            // Buzzer rendah dua denyut.
            [0, 0.3].forEach((delay) => {
                const start = audio.currentTime + delay;
                const oscillator = audio.createOscillator();
                const gain = audio.createGain();
                oscillator.type = 'sawtooth';
                oscillator.frequency.value = 110;
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(0.45, start + 0.02);
                gain.gain.setValueAtTime(0.45, start + 0.22);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.28);
                oscillator.connect(gain).connect(audio.destination);
                oscillator.start(start);
                oscillator.stop(start + 0.3);
            });
        }

        // Timer: sisa waktu dari server dihitung mundur di sini di antara dua polling.
        const timerEl = document.querySelector('[data-tv-timer]');
        const timer = { status: @js($timer['status']), remaining: {{ $timer['remaining_ms'] }}, receivedAt: performance.now() };

        function setTimer(state) {
            timer.status = state.status;
            timer.remaining = state.remaining_ms;
            timer.receivedAt = performance.now();
        }

        function renderTimer() {
            const left = timer.status === 'running'
                ? Math.max(0, timer.remaining - (performance.now() - timer.receivedAt))
                : timer.remaining;
            const seconds = Math.ceil(left / 1000);
            timerEl.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
            const finished = timer.status === 'finished' || (timer.status === 'running' && left === 0);
            timerEl.classList.toggle('finished', finished);
            timerEl.classList.toggle('warning', !finished && timer.status === 'running' && seconds <= 10);
        }

        setInterval(renderTimer, 200);
        setInterval(sync, 1500);
    </script>
</body>
</html>
