document.addEventListener('DOMContentLoaded', () => {
    let isSpinning = false;
    let soundEnabled = true;

    // 0. Proportional 16:9 Stage Auto-Scaler (Menjamin rasio sempurna saat zoom Ctrl - / Ctrl +)
    function fitTvStage() {
        const canvas = document.getElementById('stageCanvas');
        if (!canvas) return;
        const w = window.innerWidth;
        const h = window.innerHeight;
        const scale = Math.min(w / 1600, h / 900);
        canvas.style.transform = `scale(${scale})`;
    }
    window.addEventListener('resize', fitTvStage);
    fitTvStage();

    // 1. Participant Pool dari Controller Laravel
    const defaultDummyGuests = [
        'Rizky Pratama', 'Siti Aulia', 'Dimas Arya', 'Nadya Putri', 'Fauzan Hakim',
        'Bagus Setiawan', 'Putri Anggraini', 'Andi Saputra', 'Nurul Hidayah', 'Arif Rahman',
        'Budi Santoso', 'Rina Marlina', 'Citra Dewi', 'Fajar Hidayat', 'Dewi Lestari',
        'Eko Prasetyo', 'Hendra Gunawan', 'Maya Indah', 'Bayu Saputra', 'Agus Setiawan'
    ];

    let participantPool = (Array.isArray(window.doorprizeParticipants) && window.doorprizeParticipants.length > 0)
        ? window.doorprizeParticipants
        : defaultDummyGuests;

    const idleBarsStack = document.getElementById('idleBarsStack');
    const resultBarsStack = document.getElementById('resultBarsStack');
    const gachaLinesStack = document.getElementById('gachaLinesStack');
    let currentSlots = Number(idleBarsStack?.dataset.slots) || (window.doorprizeSpin?.slots) || 5;

    let activeWinners = Array(currentSlots).fill(null);
    let activeWinnerDetails = Array(currentSlots).fill(null);
    let slotLocked = Array(currentSlots).fill(false);

    // View Containers
    const viewIdle = document.getElementById('viewIdle');
    const viewSpinning = document.getElementById('viewSpinning');
    const viewResult = document.getElementById('viewResult');

    // Header & Status Texts
    const spinHeaderTxt = document.getElementById('spinHeaderTxt');
    const spinLiveStatus = document.getElementById('spinLiveStatus');

    // Celebration Modal Elements
    const popupCelebrationModal = document.getElementById('popupCelebrationModal');
    const popupWinnersGrid = document.getElementById('popupWinnersGrid');
    const btnCloseCelebrationModal = document.getElementById('btnCloseCelebrationModal');

    // 2. View Switching Helper
    function switchView(viewName) {
        const canvas = document.getElementById('stageCanvas');
        if (canvas) {
            canvas.classList.remove('state-idle', 'state-spinning', 'state-result', 'state-stopped');
            canvas.classList.add('state-' + (viewName === 'stopped' ? 'result' : viewName));
        }

        if (viewIdle) viewIdle.classList.remove('active');
        if (viewSpinning) viewSpinning.classList.remove('active');
        if (viewResult) viewResult.classList.remove('active');

        if (viewName === 'idle') {
            if (viewIdle) viewIdle.classList.add('active');
        } else if (viewName === 'spinning') {
            if (viewSpinning) viewSpinning.classList.add('active');
        } else if (viewName === 'result' || viewName === 'stopped') {
            if (viewResult) viewResult.classList.add('active');
        }
    }

    // 3. Web Audio API (Tick, Chime, Fanfare)
    let audioCtx = null;
    function getAudioContext() {
        if (!audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            audioCtx = new AudioContext();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    let lastTickTime = 0;
    function playTick() {
        if (!soundEnabled) return;
        const now = performance.now();
        if (now - lastTickTime < 75) return;
        lastTickTime = now;

        try {
            const ctx = getAudioContext();
            const t = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(680, t);
            osc.frequency.exponentialRampToValueAtTime(140, t + 0.025);

            gain.gain.setValueAtTime(0.04, t);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.025);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(t);
            osc.stop(t + 0.028);
        } catch (e) {}
    }

    function playChime(step = 0) {
        if (!soundEnabled) return;
        try {
            const ctx = getAudioContext();
            const t = ctx.currentTime;
            // Nada pentatonis mewah naik: C5, D5, E5, G5, A5, C6
            const freqs = [523.25, 587.33, 659.25, 783.99, 880.00, 1046.50];
            const f = freqs[step % freqs.length];

            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(f, t);

            gain.gain.setValueAtTime(0.18, t);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.65);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(t);
            osc.stop(t + 0.65);
        } catch (e) {}
    }

    function playFanfare() {
        if (!soundEnabled) return;
        try {
            const ctx = getAudioContext();
            const notes = [523.25, 659.25, 783.99, 1046.50, 1318.51];
            notes.forEach((freq, idx) => {
                setTimeout(() => {
                    const t = ctx.currentTime;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, t);
                    gain.gain.setValueAtTime(0.14, t);
                    gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.95);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(t);
                    osc.stop(t + 0.95);
                }, idx * 120);
            });
        } catch (e) {}
    }

    function toggleSound() {
        soundEnabled = !soundEnabled;
    }

    // 4. Confetti Celebration
    function triggerLuxuryConfetti() {
        if (typeof confetti !== 'function') return;

        const count = 220;
        const defaults = {
            origin: { y: 0.6 },
            zIndex: 1200,
            colors: ['#fde68a', '#d4af37', '#f59e0b', '#881329', '#ffffff', '#b45309']
        };

        function fire(particleRatio, opts) {
            confetti(Object.assign({}, defaults, opts, {
                particleCount: Math.floor(count * particleRatio)
            }));
        }

        fire(0.25, { spread: 26, startVelocity: 55 });
        fire(0.2, { spread: 60 });
        fire(0.35, { spread: 100, decay: 0.91, scalar: 0.8 });
        fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
        fire(0.1, { spread: 120, startVelocity: 45 });
    }

    // 5. Candidate Name Helpers
    function getName(item) {
        if (!item) return '-';
        if (typeof item === 'string') return item;
        return item.name || item.guest_name || item.title || '-';
    }

    function getCategory(item) {
        if (!item || typeof item !== 'object') return '';
        return item.category || item.group || item.table || '';
    }

    function pickRandomCandidate(excludedNames = []) {
        if (!participantPool || participantPool.length === 0) {
            return `Tamu #${Math.floor(Math.random() * 100) + 1}`;
        }
        const available = participantPool.filter(p => !excludedNames.includes(getName(p)));
        const pool = available.length > 0 ? available : participantPool;
        const idx = Math.floor(Math.random() * pool.length);
        return pool[idx];
    }

    // 6. GACHA ANIMATION: 5 Baris Horisontal dengan Arrow pada Setiap Baris
    let spinInterval = null;
    let peripheralInterval = null;
    const gachaBadgeText = document.getElementById('gachaBadgeText');

    function startSpinningAnimation() {
        if (isSpinning) return;
        isSpinning = true;

        slotLocked = Array(currentSlots).fill(false);
        if (gachaBadgeText) gachaBadgeText.textContent = `Mengacak ${currentSlots} Nama Pemenang...`;

        // Reset visual state pada ke-5 baris
        for (let i = 0; i < currentSlots; i++) {
            const line = document.getElementById(`gachaLineRow-${i}`);
            const pillCenter = document.getElementById(`rPillCenter-${i}`);
            if (line) line.classList.remove('locked');
            if (pillCenter) pillCenter.classList.remove('winner-locked');
        }

        switchView('spinning');

        // Target Center Pill berputar cepat (65ms) di setiap baris yang belum terkunci
        if (spinInterval) clearInterval(spinInterval);
        spinInterval = setInterval(() => {
            for (let i = 0; i < currentSlots; i++) {
                if (!slotLocked[i]) {
                    const candidate = pickRandomCandidate();
                    const elTarget = document.getElementById(`rTargetName-${i}`);
                    if (elTarget) elTarget.textContent = getName(candidate);
                }
            }
            playTick();
        }, 65);

        // Peripheral pills berganti setiap 130ms menciptakan ilusi roda kasino bergerak
        if (peripheralInterval) clearInterval(peripheralInterval);
        peripheralInterval = setInterval(() => {
            for (let i = 0; i < currentSlots; i++) {
                if (!slotLocked[i]) {
                    const elLE = document.getElementById(`rPillLeftEdge-${i}`);
                    const elL = document.getElementById(`rPillLeft-${i}`);
                    const elR = document.getElementById(`rPillRight-${i}`);
                    const elRE = document.getElementById(`rPillRightEdge-${i}`);

                    if (elLE) elLE.textContent = getName(pickRandomCandidate());
                    if (elL) elL.textContent = getName(pickRandomCandidate());
                    if (elR) elR.textContent = getName(pickRandomCandidate());
                    if (elRE) elRE.textContent = getName(pickRandomCandidate());
                }
            }
        }, 130);
    }

    function stopSpinningAnimation() {
        if (spinInterval) {
            clearInterval(spinInterval);
            spinInterval = null;
        }
        if (peripheralInterval) {
            clearInterval(peripheralInterval);
            peripheralInterval = null;
        }
        isSpinning = false;
    }

    // 7. DISPLAY RESULTS: Dramatis Mengunci 1 per 1 di Setiap Baris (Line 1 s/d Line 5)
    function displayResults(winners, { winnerDetails = null, instant = false } = {}) {
        if (!Array.isArray(winners) || winners.length === 0) return;

        activeWinners = [...winners];
        activeWinnerDetails = winnerDetails ? [...winnerDetails] : Array(winners.length).fill(null);

        // Update teks di viewResult (5 Baris Horizontal Emas)
        winners.forEach((winner, idx) => {
            const nameEl = document.getElementById(`winnerName-${idx}`);
            const catEl = document.getElementById(`winnerCat-${idx}`);
            if (nameEl) nameEl.textContent = getName(winner);

            const detail = (winnerDetails && winnerDetails[idx]) ? winnerDetails[idx] : winner;
            const wCat = getCategory(detail);
            if (catEl) {
                if (wCat) {
                    catEl.textContent = wCat;
                    catEl.classList.add('visible');
                } else {
                    catEl.textContent = '';
                    catEl.classList.remove('visible');
                }
            }
        });

        if (instant) {
            stopSpinningAnimation();
            switchView('result');
            return;
        }

        // Penguncian Dramatis 1 per 1 dari Line 1 s/d Line 5
        const total = Math.min(winners.length, currentSlots);
        const lockStepDelay = 520; // 520ms jeda per baris membangun antisipasi maksimal

        for (let i = 0; i < total; i++) {
            setTimeout(() => {
                slotLocked[i] = true;
                const line = document.getElementById(`gachaLineRow-${i}`);
                const pillCenter = document.getElementById(`rPillCenter-${i}`);
                const elTarget = document.getElementById(`rTargetName-${i}`);

                const winnerItem = winners[i];
                if (elTarget) elTarget.textContent = getName(winnerItem);

                if (line) line.classList.add('locked');
                if (pillCenter) pillCenter.classList.add('winner-locked');

                playChime(i);

                // Percikan konfeti mini di baris yang terkunci
                try {
                    const slotEl = document.getElementById(`lineSlot-${i}`);
                    if (slotEl && typeof confetti === 'function') {
                        const rect = slotEl.getBoundingClientRect();
                        confetti({
                            particleCount: 28,
                            spread: 60,
                            origin: {
                                x: (rect.left + rect.width * 0.5) / window.innerWidth,
                                y: (rect.top + rect.height * 0.5) / window.innerHeight
                            },
                            colors: ['#fde68a', '#d4af37', '#ffffff']
                        });
                    }
                } catch (e) {}

            }, i * lockStepDelay);
        }

        // Setelah semua baris terkunci: Fanfare, Confetti Penuh, lalu Beri Waktu Audiens Menikmati Hasil
        setTimeout(() => {
            stopSpinningAnimation();
            playFanfare();
            triggerLuxuryConfetti();

            if (gachaBadgeText) gachaBadgeText.textContent = `🎉 ${total} Pemenang Berhasil Terpilih!`;

            // Tampilkan modal popup jika ada
            if (popupWinnersGrid) {
                popupWinnersGrid.innerHTML = '';
                winners.forEach((w, idx) => {
                    const row = document.createElement('div');
                    row.className = 'popup-winner-row';
                    row.innerHTML = `
                        <div class="popup-winner-rank">${idx + 1}</div>
                        <div class="popup-winner-name">${getName(w)}</div>
                    `;
                    popupWinnersGrid.appendChild(row);
                });
            }

            // Tampilkan kartu gacha dengan 5 baris terkunci selama 2.8 detik, lalu transisi mulus ke viewResult
            setTimeout(() => {
                switchView('result');
            }, 2800);

        }, total * lockStepDelay + 350);
    }

    // 8. Standalone Local Spin (Bisa diuji dengan SPASI di TV)
    function startLocalSpin() {
        if (isSpinning) return;
        startSpinningAnimation();

        // Ambil pemenang acak tanpa duplikasi
        const chosen = [];
        const poolCopy = [...participantPool];
        for (let i = poolCopy.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [poolCopy[i], poolCopy[j]] = [poolCopy[j], poolCopy[i]];
        }

        for (let i = 0; i < currentSlots; i++) {
            chosen.push(poolCopy[i] || `Pemenang #${i + 1}`);
        }

        // Putar selama 3.5 detik lalu kunci satu per satu
        setTimeout(() => {
            displayResults(chosen, { instant: false });
        }, 3500);
    }

    // 9. Reset ke Standby
    function resetToIdle() {
        stopSpinningAnimation();
        isSpinning = false;
        slotLocked = Array(currentSlots).fill(false);
        activeWinners = Array(currentSlots).fill(null);
        activeWinnerDetails = Array(currentSlots).fill(null);

        for (let i = 0; i < currentSlots; i++) {
            const line = document.getElementById(`gachaLineRow-${i}`);
            const pillCenter = document.getElementById(`rPillCenter-${i}`);
            if (line) line.classList.remove('locked');
            if (pillCenter) pillCenter.classList.remove('winner-locked');

            const nameEl = document.getElementById(`winnerName-${i}`);
            const catEl = document.getElementById(`winnerCat-${i}`);
            if (nameEl) nameEl.textContent = '-';
            if (catEl) {
                catEl.textContent = '';
                catEl.classList.remove('visible');
            }
        }

        switchView('idle');
    }

    // 10. Keyboard Shortcuts (Untuk kemudahan operator / testing tanpa tombol visual)
    window.addEventListener('keydown', (e) => {
        if (e.code === 'Space' || e.code === 'Enter') {
            if (!isSpinning && popupCelebrationModal && !popupCelebrationModal.classList.contains('active')) {
                e.preventDefault();
                startLocalSpin();
            }
        } else if (e.key === 'f' || e.key === 'F') {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        } else if (e.key === 'm' || e.key === 'M') {
            toggleSound();
        } else if (e.key === 'r' || e.key === 'R') {
            if (!isSpinning) resetToIdle();
        } else if (e.key === 'c' || e.key === 'C') {
            if (!isSpinning) {
                triggerLuxuryConfetti();
                playFanfare();
            }
        } else if (e.key === 'Escape') {
            if (popupCelebrationModal) popupCelebrationModal.classList.remove('active');
        }
    });

    if (btnCloseCelebrationModal) {
        btnCloseCelebrationModal.addEventListener('click', () => {
            if (popupCelebrationModal) popupCelebrationModal.classList.remove('active');
        });
    }

    // 11. Remote Synchronization dengan Dasbor Admin (/doorprize)
    const remote = window.doorprizeSpin || null;
    let remoteSeq = remote ? remote.seq : 0;

    function applyRemote(state, { initial = false } = {}) {
        if (Array.isArray(state.pool) && state.pool.length > 0) {
            participantPool = state.pool;
        }

        if (state.status === 'spinning') {
            if (!isSpinning) {
                startSpinningAnimation();
            }
        } else if (state.status === 'stopped' && Array.isArray(state.winners) && state.winners.length > 0) {
            displayResults(state.winners, {
                winnerDetails: state.winner_details,
                instant: initial
            });
        } else if (state.status === 'idle' && !initial) {
            resetToIdle();
        }
    }

    const isPreviewParam = window.location.search.includes('preview=');
    if (isPreviewParam) {
        if (window.location.search.includes('preview=spinning')) {
            startSpinningAnimation();
        }
    } else if (remote) {
        applyRemote(remote, { initial: true });

        const stateUrl = window.doorprizeSpinStateUrl;
        setInterval(async () => {
            try {
                const response = await fetch(`${stateUrl}?seq=${remoteSeq}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const state = await response.json();
                if (state.seq !== remoteSeq) {
                    remoteSeq = state.seq;
                    applyRemote(state);
                }
            } catch (e) {}
        }, 1000);
    }
});
