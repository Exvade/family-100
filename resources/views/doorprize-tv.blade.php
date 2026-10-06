<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Wedding Doorprize - Anne &amp; Hanif</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <!-- Fonts: Cinzel (Game Show 3D Title), Montserrat (Clean Legible Names), Great Vibes (Wedding Calligraphy) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800;900&family=Great+Vibes&family=Montserrat:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Canvas Confetti CDN -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.4/dist/confetti.browser.min.js"></script>

    <!-- External Stage & UI Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/doorprize-tv.css') }}">
</head>
<body>

    <!-- Seamless Full-Viewport Stage View -->
    <div class="stage-viewport">

        <!-- Volumetric Background Stage Lighting -->
        <div class="stage-lighting-overlay">
            <div class="spotlight-cone l1"></div>
            <div class="spotlight-cone l2"></div>
            <div class="spotlight-cone r2"></div>
            <div class="spotlight-cone r1"></div>
        </div>

        <!-- Blue LED Matrix Wings -->
        <div class="stage-wing-wall left"></div>
        <div class="stage-wing-wall right"></div>

        <!-- Symmetrical Golden Stage Arches -->
        <div class="stage-arch-beam left"></div>
        <div class="stage-arch-beam right"></div>

        <!-- Stage Floor Reflection Ring -->
        <div class="stage-floor-reflect"></div>

        <!-- 3D TITLE EMBLEM: WEDDING DOORPRIZE (Anne & Hanif) -->
        <header class="stage-header-crest">
            <!-- Double Wedding Rings with Diamond Sparkle -->
            <div class="crest-rings">
                <svg viewBox="0 0 100 65" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="36" cy="38" r="22" stroke="url(#goldApexRing)" stroke-width="6"/>
                    <circle cx="64" cy="32" r="22" stroke="url(#goldApexRing)" stroke-width="6"/>
                    <polygon points="64,6 72,13 64,22 56,13" fill="#ffffff" filter="drop-shadow(0 0 8px #ffffff)"/>
                    <defs>
                        <linearGradient id="goldApexRing" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#fffef0"/>
                            <stop offset="45%" stop-color="#f59e0b"/>
                            <stop offset="100%" stop-color="#92400e"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>

            <!-- Crisp 3D Extruded Title -->
            <h1 class="crest-title-3d">
                <span class="title-top">WEDDING</span>
                <span class="title-bottom">DOORPRIZE</span>
            </h1>

            <!-- Dark Navy Ribbon with Couple Calligraphy -->
            <div class="crest-ribbon">
                <span class="crest-ribbon-names">Anne &amp; Hanif</span>
            </div>
        </header>

        <!-- GRAND GOLDEN MARQUEE BOARD FRAME -->
        <section class="marquee-stage-board" id="marqueeStageBoard">
            <!-- Perimeter Bulbs (Static & Sharp) -->
            <div class="marquee-bulbs-track" id="marqueeBulbsTrack"></div>

            <!-- Inner Surface -->
            <div class="marquee-inner-surface">
                <div class="board-split-columns">

                    <!-- LEFT COLUMN: 5 FAMILY 100 WINNER BARS (~62%) -->
                    <div class="col-participants-bars">
                        <!-- Top Tag -->
                        <div class="bars-top-title-badge">
                            <span>DAFTAR PEMENANG DOORPRIZE</span>
                        </div>

                        <!-- 5 Family 100 Answer Bars Stack (Displaying Participant Names!) -->
                        <div class="bars-vertical-stack">
                            <!-- Bar 1 -->
                            <div class="family-participant-bar" id="participantBar-0" data-index="0" title="Klik untuk putar baris ini (Shortcut: Angka 1)">
                                <div class="sphere-rank-badge">1</div>
                                <div class="bar-label-container">
                                    <div class="bar-rank-heading">PEMENANG #1</div>
                                    <div class="bar-status-sub" id="barStatusSub-0">Siap Diundi</div>
                                </div>
                                <div class="bar-participant-window">
                                    <div class="slot-dots-unrevealed" id="dotsDisplay-0">••••••••••••••</div>
                                    <div class="participant-name-text" id="nameDisplay-0" style="display: none;">-</div>
                                </div>
                            </div>

                            <!-- Bar 2 -->
                            <div class="family-participant-bar" id="participantBar-1" data-index="1" title="Klik untuk putar baris ini (Shortcut: Angka 2)">
                                <div class="sphere-rank-badge">2</div>
                                <div class="bar-label-container">
                                    <div class="bar-rank-heading">PEMENANG #2</div>
                                    <div class="bar-status-sub" id="barStatusSub-1">Siap Diundi</div>
                                </div>
                                <div class="bar-participant-window">
                                    <div class="slot-dots-unrevealed" id="dotsDisplay-1">••••••••••••••</div>
                                    <div class="participant-name-text" id="nameDisplay-1" style="display: none;">-</div>
                                </div>
                            </div>

                            <!-- Bar 3 -->
                            <div class="family-participant-bar" id="participantBar-2" data-index="2" title="Klik untuk putar baris ini (Shortcut: Angka 3)">
                                <div class="sphere-rank-badge">3</div>
                                <div class="bar-label-container">
                                    <div class="bar-rank-heading">PEMENANG #3</div>
                                    <div class="bar-status-sub" id="barStatusSub-2">Siap Diundi</div>
                                </div>
                                <div class="bar-participant-window">
                                    <div class="slot-dots-unrevealed" id="dotsDisplay-2">••••••••••••••</div>
                                    <div class="participant-name-text" id="nameDisplay-2" style="display: none;">-</div>
                                </div>
                            </div>

                            <!-- Bar 4 -->
                            <div class="family-participant-bar" id="participantBar-3" data-index="3" title="Klik untuk putar baris ini (Shortcut: Angka 4)">
                                <div class="sphere-rank-badge">4</div>
                                <div class="bar-label-container">
                                    <div class="bar-rank-heading">PEMENANG #4</div>
                                    <div class="bar-status-sub" id="barStatusSub-3">Siap Diundi</div>
                                </div>
                                <div class="bar-participant-window">
                                    <div class="slot-dots-unrevealed" id="dotsDisplay-3">••••••••••••••</div>
                                    <div class="participant-name-text" id="nameDisplay-3" style="display: none;">-</div>
                                </div>
                            </div>

                            <!-- Bar 5 -->
                            <div class="family-participant-bar" id="participantBar-4" data-index="4" title="Klik untuk putar baris ini (Shortcut: Angka 5)">
                                <div class="sphere-rank-badge">5</div>
                                <div class="bar-label-container">
                                    <div class="bar-rank-heading">PEMENANG #5</div>
                                    <div class="bar-status-sub" id="barStatusSub-4">Siap Diundi</div>
                                </div>
                                <div class="bar-participant-window">
                                    <div class="slot-dots-unrevealed" id="dotsDisplay-4">••••••••••••••</div>
                                    <div class="participant-name-text" id="nameDisplay-4" style="display: none;">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: SLOT FOTO MEMPELAI (~38%) -->
                    <div class="col-couple-portrait">
                        <div class="couple-portrait-outer-frame">
                            <div class="couple-portrait-card">
                                <!-- Top Plaque -->
                                <div class="portrait-top-badge">
                                    MEMPELAI BAHAGIA
                                </div>

                                <!-- ELEGANT COUPLE INITIALS & MONOGRAM DISPLAY -->
                                <div class="portrait-artwork-viewport">
                                    <!-- Golden Monogram Emblem: A & H -->
                                    <div class="portrait-monogram-circle">
                                        <div class="monogram-script-ah">A &amp; H</div>
                                    </div>

                                    <!-- Wedding Rings Icon -->
                                    <div class="wedding-rings-ornament">
                                        <svg viewBox="0 0 100 55" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="36" cy="30" r="18" stroke="url(#pRingG)" stroke-width="4.5"/>
                                            <circle cx="64" cy="25" r="18" stroke="url(#pRingG)" stroke-width="4.5"/>
                                            <polygon points="64,5 70,11 64,18 58,11" fill="#ffffff" filter="drop-shadow(0 0 4px #ffffff)"/>
                                            <defs>
                                                <linearGradient id="pRingG" x1="0" y1="0" x2="1" y2="1">
                                                    <stop offset="0%" stop-color="#fffef0"/>
                                                    <stop offset="50%" stop-color="#f59e0b"/>
                                                    <stop offset="100%" stop-color="#92400e"/>
                                                </linearGradient>
                                            </defs>
                                        </svg>
                                    </div>

                                    <div class="portrait-subtitle-label">The Wedding of</div>
                                    <div style="font-family: 'Great Vibes', cursive; font-size: clamp(1.4rem, 2vw, 1.9rem); color: #fde68a; text-shadow: 0 0 8px rgba(245, 158, 11, 0.6); margin-top: 2px;">
                                        Anne &amp; Hanif
                                    </div>
                                    <div class="portrait-date-text">Minggu, 11 Oktober 2026</div>
                                </div>

                                <!-- Bottom Ribbon -->
                                <div class="portrait-bottom-banner">
                                    <div class="portrait-couple-title">Anne &amp; Hanif</div>
                                    <div class="portrait-couple-sub">♥ Bahagia Selamanya ♥</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

    </div>



    <!-- Winner Celebration Modal Popup -->
    <div class="celebration-popup-overlay" id="popupCelebrationModal">
        <div class="celebration-popup-card">
            <h2 class="popup-header-title">SELAMAT KEPADA PARA<br>PEMENANG!</h2>
            <p class="popup-header-sub">DOORPRIZE PERNIKAHAN ANNE &amp; HANIF</p>

            <div class="popup-winners-grid" id="popupWinnersGrid"></div>

            <div>
                <button class="btn-close-popup" id="btnCloseCelebrationModal">TUTUP / LANJUTKAN</button>
                <div class="popup-dismiss-hint">TEKAN [ESC] ATAU KLIK LAYAR UNTUK MENUTUP</div>
            </div>
        </div>
    </div>

    <!-- Direct Controller Data Injection (Monolith / Non-API) -->
    <script>
        window.doorprizeParticipants = @json($participants ?? []);
        window.doorprizeWedding = @json($wedding ?? null);
    </script>
    <!-- Interactive TV Script -->
    <script src="{{ asset('js/doorprize-tv.js') }}"></script>
</body>
</html>
