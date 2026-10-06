document.addEventListener('DOMContentLoaded', () => {
            let isSpinning = false;
            let soundEnabled = true;

            // Ambil data langsung dari Controller Laravel ($participants), fallback ke dummy jika belum ada
            const defaultDummyGuests = [
                'Budi Santoso', 'Rina Marlina', 'Dimas Pratama', 'Siti Aisyah', 'Rizky Firmansyah',
                'Andi Wijaya', 'Citra Dewi', 'Fajar Hidayat', 'Dewi Lestari', 'Eko Prasetyo',
                'Hendra Gunawan', 'Maya Indah', 'Bayu Saputra', 'Nurul Hidayah', 'Agus Setiawan',
                'Mega Wulandari', 'Reza Pahlevi', 'Taufik Rahman', 'Indah Permata', 'Yusuf Maulana',
                'Anisa Rahmawati', 'Bambang Tri', 'Dian Sastrowardoyo', 'Guruh Soekarno', 'Wulan Guritno'
            ];

            const participantPool = (Array.isArray(window.doorprizeParticipants) && window.doorprizeParticipants.length > 0)
                ? window.doorprizeParticipants
                : defaultDummyGuests;

            let activeWinners = [null, null, null, null, null];

            const popupCelebrationModal = document.getElementById('popupCelebrationModal');
            const popupWinnersGrid = document.getElementById('popupWinnersGrid');
            const btnCloseCelebrationModal = document.getElementById('btnCloseCelebrationModal');

            // 1. Render Static Marquee Bulbs
            const bulbsTrack = document.getElementById('marqueeBulbsTrack');
            function drawMarqueeBulbs() {
                bulbsTrack.innerHTML = '';
                const rect = bulbsTrack.getBoundingClientRect();
                const w = rect.width;
                const h = rect.height;
                const gap = 44;

                for (let x = 24; x <= w - 24; x += gap) {
                    insertBulb(x, -6);
                    insertBulb(x, h - 7);
                }
                for (let y = 24; y <= h - 24; y += gap) {
                    insertBulb(-6, y);
                    insertBulb(w - 7, y);
                }
            }
            function insertBulb(x, y) {
                const b = document.createElement('div');
                b.className = 'static-bulb';
                b.style.left = `${x}px`;
                b.style.top = `${y}px`;
                bulbsTrack.appendChild(b);
            }
            drawMarqueeBulbs();
            window.addEventListener('resize', drawMarqueeBulbs);

            // 3. Procedural Web Audio API
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

            // Sound Management (Throttled, Smooth Harmonic Web Audio)
            let lastTickTime = 0;

            function playTick() {
                if (!soundEnabled) return;
                const now = performance.now();
                // Throttle: Maksimal 1 suara setiap 75ms agar suara tidak bertabrakan/menumpuk saat 5 bar berputar bersamaan
                if (now - lastTickTime < 75) return;
                lastTickTime = now;

                try {
                    const ctx = getAudioContext();
                    const t = ctx.currentTime;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();

                    // Suara 'click' mekanik halus (sine wave dengan pitch drop cepat), bukan triangle acak yang bising
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(700, t);
                    osc.frequency.exponentialRampToValueAtTime(160, t + 0.028);

                    gain.gain.setValueAtTime(0.045, t);
                    gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.028);

                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(t);
                    osc.stop(t + 0.03);
                } catch(e) {}
            }

            function playChime(step = 0) {
                if (!soundEnabled) return;
                try {
                    const ctx = getAudioContext();
                    const t = ctx.currentTime;
                    // Skala pentatonik ceria & hangat (Do - Mi - Sol - La - Do Tinggi)
                    const freqs = [523.25, 659.25, 783.99, 880.00, 1046.50];
                    const f = freqs[step % freqs.length];

                    // Fundamental tone (lonceng lembut)
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(f, t);

                    gain.gain.setValueAtTime(0.12, t);
                    gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.45);

                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(t);
                    osc.stop(t + 0.45);
                } catch(e) {}
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
                            gain.gain.setValueAtTime(0.11, t);
                            gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.8);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start(t);
                            osc.stop(t + 0.8);
                        }, idx * 120);
                    });
                } catch(e) {}
            }

            function toggleSound() {
                soundEnabled = !soundEnabled;
                getAudioContext();
                if (soundEnabled) {
                    playChime(0);
                }
            }

            // AudioContext auto-unlock on user interaction
            window.addEventListener('click', () => getAudioContext(), { once: true });
            window.addEventListener('keydown', () => getAudioContext(), { once: true });

            // 4. Spin Slot Bar with Guest Names (No "meja")
            function pickRandomCandidate(excluded = []) {
                const available = participantPool.filter(name => !excluded.includes(name));
                if (available.length > 0) {
                    return available[Math.floor(Math.random() * available.length)];
                }
                return participantPool[Math.floor(Math.random() * participantPool.length)] || 'Tamu Undangan';
            }

            function spinSlotBar(index, chosenName, onComplete) {
                const bar = document.getElementById(`participantBar-${index}`);
                const dots = document.getElementById(`dotsDisplay-${index}`);
                const nameDisplay = document.getElementById(`nameDisplay-${index}`);
                const statusSub = document.getElementById(`barStatusSub-${index}`);

                bar.classList.remove('revealed');
                dots.style.display = 'none';
                nameDisplay.style.display = 'block';
                statusSub.textContent = 'Mengundi Nama...';
                statusSub.style.color = '#fde68a';

                let rolls = 0;
                const maxRolls = 18 + index * 5;
                const interval = setInterval(() => {
                    const randomName = participantPool[Math.floor(Math.random() * participantPool.length)];
                    nameDisplay.textContent = randomName;
                    playTick();
                    rolls++;

                    if (rolls >= maxRolls) {
                        clearInterval(interval);
                        const winner = chosenName || pickRandomCandidate();
                        activeWinners[index] = winner;
                        nameDisplay.textContent = winner;
                        bar.classList.add('revealed');
                        statusSub.textContent = 'Pemenang Terpilih';
                        statusSub.style.color = '#55efc4';
                        playChime(index);

                        // Confetti burst on this bar
                        try {
                            const rect = bar.getBoundingClientRect();
                            confetti({
                                particleCount: 30,
                                spread: 55,
                                origin: {
                                    x: (rect.left + rect.width / 2) / window.innerWidth,
                                    y: (rect.top + rect.height / 2) / window.innerHeight
                                }
                            });
                        } catch(e) {}

                        if (onComplete) {
                            onComplete(winner);
                        } else {
                            // Jika diputar satu per satu (angka 1-5 atau klik langsung), cek apakah kelima baris sudah terundi semua
                            const allRevealed = activeWinners.every(w => w !== null);
                            if (allRevealed && !isSpinning) {
                                setTimeout(() => {
                                    playFanfare();
                                }, 250);
                                triggerCelebrationPopup([...activeWinners]);
                            }
                        }
                    }
                }, 65);
            }

            // 5. Spin All 5 Bars Sequentially
            function spinAllBars() {
                if (isSpinning) return;
                isSpinning = true;

                // Pick 5 unique names
                const selectedWinners = [];
                const poolCopy = [...participantPool];
                for (let i = poolCopy.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [poolCopy[i], poolCopy[j]] = [poolCopy[j], poolCopy[i]];
                }

                for (let i = 0; i < 5; i++) {
                    selectedWinners.push(poolCopy[i] || `Pemenang #${i+1}`);
                }

                let finished = 0;
                selectedWinners.forEach((winnerName, idx) => {
                    setTimeout(() => {
                        spinSlotBar(idx, winnerName, () => {
                            finished++;
                            if (finished === 5) {
                                isSpinning = false;
                                setTimeout(() => {
                                    playFanfare();
                                }, 250);
                                triggerCelebrationPopup(selectedWinners);
                            }
                        });
                    }, idx * 340);
                });
            }

            // Click any row directly to spin that row individually
            document.querySelectorAll('.family-participant-bar').forEach(bar => {
                bar.addEventListener('click', () => {
                    if (isSpinning || popupCelebrationModal.classList.contains('active')) return;
                    const idx = parseInt(bar.dataset.index, 10);
                    const alreadyWon = activeWinners.filter(w => w !== null);
                    const chosen = pickRandomCandidate(alreadyWon);
                    spinSlotBar(idx, chosen);
                });
            });

            // 6. Celebration Modal Popup
            function triggerCelebrationPopup(winners) {
                try {
                    const duration = 3500;
                    const end = Date.now() + duration;
                    const colors = ['#f59e0b', '#fffef0', '#3b82f6', '#f43f5e', '#10b981'];

                    (function frame() {
                        confetti({ particleCount: 8, angle: 60, spread: 55, origin: { x: 0, y: 0.7 }, colors: colors });
                        confetti({ particleCount: 8, angle: 120, spread: 55, origin: { x: 1, y: 0.7 }, colors: colors });
                        if (Date.now() < end) {
                            requestAnimationFrame(frame);
                        }
                    }());
                } catch(e) {}

                popupWinnersGrid.innerHTML = '';
                if (winners && winners.length > 0) {
                    winners.forEach((name, idx) => {
                        const card = document.createElement('div');
                        card.className = 'winner-card-cell';
                        card.innerHTML = `
                            <div class="winner-card-rank">PEMENANG #${idx + 1}</div>
                            <div class="winner-card-name">${escapeHtml(name)}</div>
                            <div class="winner-card-tag">TAMU TERPILIH</div>
                        `;
                        popupWinnersGrid.appendChild(card);
                    });
                }

                setTimeout(() => {
                    popupCelebrationModal.classList.add('active');
                }, 800);
            }

            function escapeHtml(text) {
                if (!text) return '';
                return text.replace(/[&<>"']/g, function(m) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
                });
            }

            btnCloseCelebrationModal.addEventListener('click', (e) => {
                e.stopPropagation();
                popupCelebrationModal.classList.remove('active');
            });

            popupCelebrationModal.addEventListener('click', (e) => {
                if (e.target === popupCelebrationModal) {
                    popupCelebrationModal.classList.remove('active');
                }
            });

            // 7. Full Keyboard Shortcuts for TV Game Show Operator
            window.addEventListener('keydown', (e) => {
                if (e.code === 'Space' || e.code === 'Enter') {
                    if (!isSpinning && !popupCelebrationModal.classList.contains('active')) {
                        e.preventDefault();
                        spinAllBars();
                    }
                } else if (['1', '2', '3', '4', '5'].includes(e.key)) {
                    if (!isSpinning && !popupCelebrationModal.classList.contains('active')) {
                        e.preventDefault();
                        const idx = parseInt(e.key, 10) - 1;
                        const alreadyWon = activeWinners.filter(w => w !== null);
                        const chosen = pickRandomCandidate(alreadyWon);
                        spinSlotBar(idx, chosen);
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
                    if (isSpinning) return;
                    for (let idx = 0; idx < 5; idx++) {
                        const bar = document.getElementById(`participantBar-${idx}`);
                        const dots = document.getElementById(`dotsDisplay-${idx}`);
                        const nameDisplay = document.getElementById(`nameDisplay-${idx}`);
                        const statusSub = document.getElementById(`barStatusSub-${idx}`);
                        bar.classList.remove('revealed');
                        dots.style.display = 'block';
                        nameDisplay.style.display = 'none';
                        statusSub.textContent = 'Siap Diundi';
                        statusSub.style.color = '#93c5fd';
                        activeWinners[idx] = null;
                    }
                } else if (e.key === 'c' || e.key === 'C') {
                    // Shortcut C untuk membuka kembali pop-up pemenang jika kelima nama sudah terundi
                    if (!isSpinning && activeWinners.every(w => w !== null)) {
                        triggerCelebrationPopup([...activeWinners]);
                    }
                } else if (e.key === 'Escape') {
                    popupCelebrationModal.classList.remove('active');
                }
            });
        });
