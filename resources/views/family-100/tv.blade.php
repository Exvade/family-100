<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Family 100 - {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800;900&family=Great+Vibes&family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">

    <!-- Canvas Confetti CDN -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.4/dist/confetti.browser.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            user-select: none;
        }

        body {
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            background: #020617 url("{{ asset('images/background-quiz.png') }}") no-repeat center center;
            background-size: cover;
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #ffffff;
            position: relative;
        }

        /* Video background panggung TV (Muted & Loop) */
        .tv-bg-video {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 100vw;
            height: 100vh;
            min-width: 100vw;
            min-height: 100vh;
            transform: translate(-50%, -50%);
            object-fit: cover;
            object-position: center center;
            z-index: 1;
            pointer-events: none;
        }

        /* Banner Prompt Unlock Audio (Bila autoplay tertahan browser) */
        .tv-audio-unlock-banner {
            position: fixed;
            top: 2vh;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(220, 38, 38, 0.9);
            border: 2px solid #fecaca;
            color: #ffffff;
            font-size: clamp(0.85rem, 1.6vh, 1.15rem);
            font-weight: 800;
            padding: 0.5rem 1.8rem;
            border-radius: 9999px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.7), 0 0 20px rgba(239, 68, 68, 0.6);
            z-index: 200;
            cursor: pointer;
            animation: unlockPulse 1.8s infinite;
            backdrop-filter: blur(8px);
        }

        @keyframes unlockPulse {
            0%, 100% { transform: translateX(-50%) scale(1); }
            50% { transform: translateX(-50%) scale(1.04); }
        }

        /* ========================================================
           1. PANGGUNG KUIS FAMILY 100
           ======================================================== */
        .tv-stage-layer {
            position: absolute;
            inset: 0;
            width: 100vw;
            height: 100vh;
            z-index: 5;
            transition: opacity 0.4s ease, transform 0.4s ease;
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

        /* Indikator Tanda X Persisten di Panggung TV */
        .tv-strikes-container {
            position: fixed;
            bottom: 2.2vh;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: clamp(0.5rem, 1.1vw, 1.2rem);
            z-index: 25;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .tv-strike-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            animation: strikeMarkPop 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .tv-strike-mark svg {
            width: clamp(2.4rem, 4.8vh, 3.8rem);
            height: clamp(2.4rem, 4.8vh, 3.8rem);
            color: #ef4444;
            filter: drop-shadow(0 0 10px #dc2626) drop-shadow(0 0 22px #b91c1c);
        }

        .tv-strike-mark-extra {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(1.2rem, 2.4vh, 1.8rem);
            font-weight: 900;
            color: #ef4444;
            filter: drop-shadow(0 0 8px #dc2626) drop-shadow(0 0 16px #b91c1c);
            margin-left: 0.3rem;
            animation: strikeMarkPop 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes strikeMarkPop {
            0% { transform: scale(0.3) rotate(-15deg); opacity: 0; }
            70% { transform: scale(1.15) rotate(4deg); opacity: 1; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        /* ========================================================
           2. PANGGUNG HADIAH 3D (BURGUNDY & GOLD - DOORPRIZE THEME)
           ======================================================== */
        #stageGiftWrapper {
            position: fixed;
            inset: 0;
            width: 100vw;
            height: 100vh;
            z-index: 15;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: clamp(0.8rem, 2.2vh, 1.8rem) clamp(1.2rem, 3vw, 3.5rem);
            background: #0d0103;
            overflow: hidden;
        }

        .gift-bg-video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            pointer-events: none;
        }

        /* Ornate Double Gold Borders (seperti Doorprize TV) */
        .stage-border-outer {
            position: absolute;
            inset: clamp(8px, 1.4vh, 16px);
            border: 1.5px solid rgba(212, 175, 55, 0.55);
            border-radius: 6px;
            pointer-events: none;
            z-index: 6;
        }

        .stage-border-inner {
            position: absolute;
            inset: clamp(12px, 2vh, 24px);
            border: 1px solid rgba(212, 175, 55, 0.28);
            border-radius: 4px;
            pointer-events: none;
            z-index: 6;
        }

        /* Header Panggung Hadiah Burgundy */
        .gift-stage-header {
            text-align: center;
            position: relative;
            z-index: 8;
            margin-top: clamp(0.2rem, 0.8vh, 0.6rem);
            flex: none;
        }

        .stage-wedding-title {
            font-family: 'Great Vibes', cursive;
            font-size: clamp(2.4rem, 4.8vh, 3.8rem);
            color: #fef08a;
            text-shadow: 0 0 15px rgba(254, 240, 138, 0.6), 0 2px 8px rgba(0, 0, 0, 0.9);
            line-height: 1;
            margin-bottom: 0.15rem;
        }

        .stage-main-title {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.6rem, 3.6vh, 2.8rem);
            font-weight: 900;
            letter-spacing: 0.14em;
            background: linear-gradient(180deg, #ffffff 0%, #fef08a 35%, #eab308 70%, #ca8a04 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.9)) drop-shadow(0 0 25px rgba(234, 179, 8, 0.5));
            text-transform: uppercase;
            line-height: 1.15;
        }

        .wedding-gold-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 0.3rem auto 0.4rem auto;
            width: clamp(180px, 20vw, 260px);
        }

        .wedding-gold-divider .div-line {
            flex: 1;
            height: 1.5px;
            background: linear-gradient(90deg, transparent 0%, #d4af37 50%, transparent 100%);
        }

        .wedding-gold-divider .div-heart {
            font-size: 13px;
            color: #d4af37;
            filter: drop-shadow(0 0 4px rgba(212, 175, 55, 0.8));
        }

        .stage-sub-badge {
            display: inline-block;
            background: rgba(45, 5, 14, 0.85);
            border: 1.5px solid rgba(212, 175, 55, 0.7);
            color: #fef08a;
            font-family: 'Cinzel', serif;
            font-size: clamp(0.72rem, 1.3vh, 0.92rem);
            font-weight: 700;
            letter-spacing: 0.18em;
            padding: 0.22rem 1.3rem;
            border-radius: 9999px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.7), 0 0 15px rgba(120, 16, 35, 0.6);
            text-transform: uppercase;
        }

        /* Grid Kotak Hadiah 3D */
        .gift-grid-wrapper {
            flex: 1;
            width: 100%;
            max-width: 1550px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(0.4rem, 1.2vh, 1.2rem) 0;
            position: relative;
            z-index: 8;
        }

        .gift-grid {
            display: grid;
            width: 100%;
            height: 100%;
            max-height: 65vh;
            gap: clamp(0.5rem, 1.1vh, 1.2rem) clamp(0.5rem, 1vw, 1.4rem);
            grid-template-columns: repeat(5, 1fr);
            grid-auto-rows: 1fr;
            perspective: 1200px;
        }

        .gift-grid.cols-6 {
            grid-template-columns: repeat(6, 1fr);
            gap: clamp(0.35rem, 0.8vh, 0.75rem) clamp(0.35rem, 0.75vw, 0.9rem);
            max-height: 68vh;
        }

        .gift-grid.cols-6 .gift-number-badge {
            width: clamp(2.3rem, 4.4vh, 3.4rem);
            height: clamp(2.3rem, 4.4vh, 3.4rem);
            font-size: clamp(1.15rem, 2.2vh, 1.7rem);
            margin-top: clamp(0.2rem, 0.5vh, 0.5rem);
        }

        .gift-grid.cols-6 .gift-bow svg {
            width: clamp(24px, 3.2vh, 32px);
            height: clamp(24px, 3.2vh, 32px);
        }

        .gift-grid.cols-6 .gift-tap-hint {
            font-size: clamp(0.52rem, 0.95vh, 0.7rem);
            margin-top: 0.2rem;
        }

        .gift-grid.cols-6 .opened-box-name {
            font-size: clamp(0.8rem, 1.4vh, 1.15rem);
        }

        /* Kartu Kotak Hadiah Burgundy */
        .gift-box-item {
            position: relative;
            border-radius: 16px;
            cursor: pointer;
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.35s ease;
            transform-style: preserve-3d;
        }

        .gift-box-item:hover:not(.opened) {
            transform: translateY(-8px) scale(1.035);
            box-shadow: 0 18px 36px rgba(0, 0, 0, 0.9), 0 0 30px rgba(245, 158, 11, 0.6), 0 0 45px rgba(170, 25, 52, 0.5);
        }

        .gift-box-card {
            width: 100%;
            height: 100%;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: clamp(0.5rem, 1vh, 1rem);
            position: relative;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* State Tertutup: Burgundy Velvet dengan Border Emas */
        .gift-box-item:not(.opened) .gift-box-card {
            background: radial-gradient(circle at 50% 30%, #781023 0%, #540a18 35%, #35050e 70%, #1c0207 100%);
            border: 2px solid #d4af37;
            box-shadow: inset 0 2px 4px rgba(255, 235, 240, 0.25), 0 10px 25px rgba(0, 0, 0, 0.85), 0 0 18px rgba(170, 25, 52, 0.35);
        }

        .gift-box-item:hover:not(.opened) .gift-box-card {
            border-color: #fde68a;
            box-shadow: inset 0 2px 8px rgba(255, 255, 255, 0.4), 0 12px 30px rgba(0, 0, 0, 0.9), 0 0 25px rgba(245, 158, 11, 0.5);
        }

        /* Pita Emas Mewah */
        .gift-ribbon-v {
            position: absolute;
            top: 0;
            bottom: 0;
            width: clamp(14px, 1.6vw, 24px);
            background: linear-gradient(90deg, #92400e 0%, #fde68a 35%, #d4af37 55%, #92400e 100%);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.6);
            z-index: 2;
        }

        .gift-ribbon-h {
            position: absolute;
            left: 0;
            right: 0;
            height: clamp(14px, 1.6vw, 24px);
            background: linear-gradient(180deg, #92400e 0%, #fde68a 35%, #d4af37 55%, #92400e 100%);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.6);
            z-index: 2;
        }

        /* Simpul Pita Emas (Bow) */
        .gift-bow {
            position: absolute;
            top: clamp(6px, 1vh, 12px);
            font-size: clamp(1.4rem, 2.8vh, 2.4rem);
            z-index: 4;
            filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.7));
            animation: bowFloat 3s ease-in-out infinite;
        }

        @keyframes bowFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        /* Medali Nomor Kotak Burgundy & Emas */
        .gift-number-badge {
            position: relative;
            z-index: 5;
            width: clamp(2.8rem, 5.5vh, 4.4rem);
            height: clamp(2.8rem, 5.5vh, 4.4rem);
            border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, #35050e 0%, #200308 50%, #100103 100%);
            border: 2.5px solid #fde68a;
            color: #fef08a;
            font-family: 'Cinzel', serif;
            font-size: clamp(1.4rem, 2.8vh, 2.2rem);
            font-weight: 900;
            display: grid;
            place-items: center;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.9), inset 0 2px 4px rgba(254, 230, 138, 0.4), 0 0 15px rgba(212, 175, 55, 0.5);
            margin-top: clamp(0.4rem, 1vh, 0.8rem);
        }

        .gift-tap-hint {
            position: relative;
            z-index: 5;
            font-size: clamp(0.6rem, 1.1vh, 0.8rem);
            font-weight: 700;
            color: #fef08a;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.9);
            margin-top: 0.4rem;
        }

        /* State Terbuka */
        .gift-box-item.opened {
            cursor: default;
        }

        .gift-box-item.opened .gift-box-card {
            background: radial-gradient(circle at 50% 25%, #420611 0%, #250308 60%, #120104 100%);
            border: 2.5px solid #d4af37;
            box-shadow: inset 0 0 25px rgba(212, 175, 55, 0.25), 0 12px 28px rgba(0, 0, 0, 0.95), 0 0 20px rgba(170, 25, 52, 0.5);
            animation: giftRevealFlash 0.6s ease-out;
        }

        .gift-box-item.opened .gift-ribbon-v,
        .gift-box-item.opened .gift-ribbon-h,
        .gift-box-item.opened .gift-bow,
        .gift-box-item.opened .gift-number-badge,
        .gift-box-item.opened .gift-tap-hint {
            display: none;
        }

        .gift-opened-content {
            display: none;
            width: 100%;
            height: 100%;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 0.4rem;
        }

        .gift-box-item.opened .gift-opened-content {
            display: flex;
        }

        .opened-box-number {
            font-family: 'Cinzel', serif;
            font-size: clamp(0.7rem, 1.3vh, 0.95rem);
            color: #fde68a;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }

        .opened-box-name {
            font-size: clamp(0.95rem, 1.8vh, 1.45rem);
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            line-height: 1.25;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.95), 0 0 15px rgba(254, 240, 138, 0.6);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .opened-box-winner {
            margin-top: 0.4rem;
            font-size: clamp(0.7rem, 1.2vh, 0.85rem);
            color: #fef08a;
            font-weight: 700;
            background: linear-gradient(90deg, rgba(84, 10, 24, 0.9) 0%, rgba(120, 16, 35, 0.95) 50%, rgba(84, 10, 24, 0.9) 100%);
            border: 1px solid rgba(212, 175, 55, 0.6);
            padding: 0.2rem 0.8rem;
            border-radius: 9999px;
            max-width: 95%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
        }

        @keyframes giftRevealFlash {
            0% { transform: scale(0.85); filter: brightness(2); }
            50% { transform: scale(1.08); filter: brightness(1.5); }
            100% { transform: scale(1); filter: brightness(1); }
        }

        .gift-stage-footer {
            position: relative;
            z-index: 8;
            color: rgba(254, 240, 138, 0.75);
            font-size: clamp(0.75rem, 1.3vh, 0.9rem);
            letter-spacing: 0.08em;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
            margin-bottom: 0.2rem;
        }

        /* Modal Overlay Hadiah Terbuka (Burgundy Celebration Popup) */
        .tv-celebration-modal {
            position: fixed;
            inset: 0;
            background: rgba(13, 1, 3, 0.92);
            backdrop-filter: blur(10px);
            z-index: 100;
            display: grid;
            place-items: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .tv-celebration-modal.show {
            opacity: 1;
            pointer-events: auto;
        }

        .celebration-card {
            background: radial-gradient(circle at 50% 20%, #540a18 0%, #35050e 45%, #1c0207 80%, #0d0103 100%);
            border: 3.5px solid #d4af37;
            box-shadow: 0 0 60px rgba(170, 25, 52, 0.6), 0 0 40px rgba(212, 175, 55, 0.5), 0 30px 80px rgba(0, 0, 0, 0.98);
            border-radius: 28px;
            padding: clamp(2rem, 5vh, 3.5rem) clamp(2.5rem, 5vw, 5rem);
            text-align: center;
            max-width: min(850px, 90vw);
            transform: scale(0.6) translateY(40px);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .tv-celebration-modal.show .celebration-card {
            transform: scale(1) translateY(0);
        }

        .celebration-box-number {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.2rem, 2.5vh, 1.8rem);
            color: #fde68a;
            font-weight: 800;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .celebration-heading {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.5rem, 3.2vh, 2.4rem);
            font-weight: 900;
            color: #ffffff;
            letter-spacing: 0.08em;
            margin-bottom: 1.2rem;
            text-transform: uppercase;
        }

        .celebration-gift-name {
            font-size: clamp(2.2rem, 5.5vh, 4.2rem);
            font-weight: 900;
            line-height: 1.15;
            background: linear-gradient(180deg, #ffffff 0%, #fef08a 35%, #f59e0b 70%, #d97706 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.95)) drop-shadow(0 0 30px rgba(234, 179, 8, 0.6));
            margin: 1rem 0;
            text-transform: uppercase;
        }

        .celebration-gift-desc {
            font-size: clamp(1rem, 2vh, 1.4rem);
            color: #fde68a;
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }

        .celebration-winner-badge {
            display: inline-block;
            background: linear-gradient(90deg, #781023 0%, #aa1934 50%, #781023 100%);
            border: 2px solid #fde68a;
            color: #ffffff;
            font-weight: 800;
            font-size: clamp(1.1rem, 2.2vh, 1.6rem);
            padding: 0.5rem 2rem;
            border-radius: 9999px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.7), 0 0 20px rgba(170, 25, 52, 0.6);
        }

        .celebration-timer-container {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 9999px;
            margin-top: 1.8rem;
            overflow: hidden;
            position: relative;
        }

        .celebration-timer-bar {
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, #92400e 0%, #d4af37 50%, #fde68a 100%);
            box-shadow: 0 0 12px rgba(250, 204, 21, 0.8);
            border-radius: 9999px;
            transform-origin: left center;
        }

        .celebration-close-hint {
            margin-top: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.6rem;
            font-size: clamp(0.75rem, 1.4vh, 0.95rem);
            color: rgba(255, 255, 255, 0.7);
        }

        .celebration-timer-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(120, 16, 35, 0.5);
            border: 1px solid rgba(212, 175, 55, 0.55);
            color: #fef3c7;
            padding: 0.25rem 0.85rem;
            border-radius: 9999px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .celebration-manual-hint {
            color: rgba(255, 255, 255, 0.45);
            font-size: clamp(0.7rem, 1.2vh, 0.82rem);
            letter-spacing: 0.04em;
        }

        /* ========================================================
           3. KONTROL MENGAMBANG (SHARED)
           ======================================================== */
        .tv-controls-bar {
            position: fixed;
            bottom: 2vh;
            right: 2.5vw;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            z-index: 30;
        }

        .tv-ctrl-btn {
            background: rgba(3, 14, 43, 0.85);
            border: 1.5px solid rgba(212, 175, 55, 0.6);
            color: #fef08a;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.45rem 1rem;
            border-radius: 9999px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(6px);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
        }

        .tv-ctrl-btn:hover {
            background: rgba(13, 54, 145, 0.95);
            border-color: #ffd700;
            color: #ffffff;
            transform: scale(1.05);
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
    <!-- Background Video Panggung TV (Muted & Loop) -->
    <video class="tv-bg-video" autoplay loop muted playsinline preload="auto" poster="{{ asset('images/background-quiz.png') }}">
        <source src="{{ asset('videos/background.mp4') }}" type="video/mp4">
    </video>

    <!-- Banner Prompt Unlock Audio (Jika Autoplay diblokir browser saat awal buka) -->
    <div id="audioUnlockBanner" class="tv-audio-unlock-banner">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -3px; margin-right: 6px;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/></svg>
        <span>Klik di layar mana saja untuk mengaktifkan suara panggung</span>
    </div>

    <!-- ========================================================
         LAYER 1: PANGGUNG KUIS FAMILY 100
         ======================================================== -->
    <div id="stageQuizWrapper" class="tv-stage-layer" style="{{ ($tvMode ?? 'quiz') === 'gift' ? 'display: none;' : 'display: block;' }}">
        <main class="tv-screen-container">
            @if (! $question || $answers->isEmpty())
                <div class="tv-empty-state">Menunggu babak kuis dimulai...</div>
            @else
                <div class="tv-board {{ $answers->count() >= 8 ? 'tv-board-dense' : '' }}">
                    @foreach ($answers as $answer)
                        <div class="tv-slot {{ $answer->is_answered ? 'revealed' : '' }}" data-slot="{{ $answer->id }}">
                            <div class="tv-card-inner">
                                <div class="tv-card-front">
                                    <span class="tv-rank">{{ $loop->iteration }}</span>
                                    <div class="tv-shutter"></div>
                                    <span class="tv-empty-badge"></span>
                                </div>
                                <div class="tv-card-back">
                                    <span class="tv-rank">{{ $loop->iteration }}</span>
                                    <span class="tv-answer-text">{{ $answer->answer }}</span>
                                    <span class="tv-answer-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5l10 -10"/></svg></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </main>

        <!-- Overlay Salah (X Merah Besar + Buzzer) -->
        <div class="tv-strike" data-tv-strike aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"><path d="M5 5l14 14M19 5L5 19"/></svg>
        </div>

        <!-- Indikator Salah (X) Persisten di Layar TV (Maksimal 10 tanda X yang ditampilkan) -->
        <div class="tv-strikes-container" data-tv-strikes style="display: none;" aria-label="Indikator Jawaban Salah"></div>
    </div>

    <!-- ========================================================
         LAYER 2: PANGGUNG HADIAH 3D (BURGUNDY & GOLD - DOORPRIZE THEME)
         ======================================================== -->
    <div id="stageGiftWrapper" class="tv-stage-layer gift-stage-burgundy" style="{{ ($tvMode ?? 'quiz') === 'gift' ? 'display: flex;' : 'display: none;' }}">
        <!-- Background Video Hadiah Panggung (Muted & Loop) -->
        <video class="gift-bg-video" autoplay loop muted playsinline preload="auto" poster="{{ asset('images/bg-burgundy-clean.jpg') }}">
            <source src="{{ asset('videos/background-hadiah-quiz.mp4') }}" type="video/mp4">
        </video>

        <!-- 1. ORNATE DOUBLE GOLD BORDERS -->
        <div class="stage-border-outer"></div>
        <div class="stage-border-inner"></div>

        <!-- Header Panggung Hadiah Burgundy -->
        <header class="gift-stage-header">
            <div class="stage-wedding-title">Anne &amp; Hanif</div>
            <h1 class="stage-main-title">Pilih Hadiah Kejutan</h1>
            <div class="wedding-gold-divider">
                <span class="div-line"></span>
                <span class="div-heart">♥</span>
                <span class="div-line"></span>
            </div>
            <div class="stage-sub-badge">Family 100 • Bonus Round</div>
        </header>

        <!-- Grid Kotak Hadiah 3D Burgundy & Emas -->
        <section class="gift-grid-wrapper">
            <div class="gift-grid {{ $giftCount > 20 ? 'cols-6' : '' }}" id="tvGiftGrid">
                @foreach ($gifts as $gift)
                    <div class="gift-box-item {{ $gift->is_opened ? 'opened' : '' }}" data-gift-id="{{ $gift->id }}" data-number="{{ $gift->number }}" data-name="{{ $gift->name }}" data-description="{{ $gift->description }}" data-winner="{{ $gift->winner_name }}" data-open-url="{{ route('family-100.gifts.open', $gift) }}">
                        <div class="gift-box-card">
                            <div class="gift-ribbon-v"></div>
                            <div class="gift-ribbon-h"></div>
                            <div class="gift-bow">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" width="36" height="36">
                                    <defs>
                                        <linearGradient id="bowGoldGrad_{{ $gift->id }}" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#fff8ed" />
                                            <stop offset="35%" stop-color="#fde68a" />
                                            <stop offset="70%" stop-color="#d4af37" />
                                            <stop offset="100%" stop-color="#92400e" />
                                        </linearGradient>
                                    </defs>
                                    <path d="M18 18 C12 10, 4 12, 6 18 C8 24, 15 20, 18 18 Z" fill="url(#bowGoldGrad_{{ $gift->id }})" />
                                    <path d="M18 18 C24 10, 32 12, 30 18 C28 24, 21 20, 18 18 Z" fill="url(#bowGoldGrad_{{ $gift->id }})" />
                                    <path d="M16 19 L11 28 L14 26 L17 29 Z" fill="#92400e" />
                                    <path d="M20 19 L25 28 L22 26 L19 29 Z" fill="#92400e" />
                                    <circle cx="18" cy="18" r="3.2" fill="#fff8ed" stroke="#92400e" stroke-width="1" />
                                </svg>
                            </div>

                            <div class="gift-number-badge">
                                {{ $gift->number }}
                            </div>
                            <div class="gift-tap-hint">Pilih Kotak</div>

                            <div class="gift-opened-content">
                                <span class="opened-box-number">Kotak #{{ $gift->number }}</span>
                                <div class="opened-box-name">{{ $gift->name }}</div>
                                @if ($gift->winner_name)
                                    <div class="opened-box-winner">{{ $gift->winner_name }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Footer Hint -->
        <footer class="gift-stage-footer text-center">
            Klik salah satu kotak hadiah di layar untuk membuka kejutan
        </footer>
    </div>

    <!-- Modal Popup Selebrasi Hadiah Terbuka -->
    <div class="tv-celebration-modal" id="modalCelebration">
        <div class="celebration-card">
            <div class="celebration-box-number" id="celebBoxNumber">KOTAK HADIAH #1</div>
            <div class="celebration-heading">SELAMAT! ANDA MENDAPATKAN</div>
            <div class="celebration-gift-name" id="celebGiftName">NAMA HADIAH</div>
            <div class="celebration-gift-desc" id="celebGiftDesc"></div>
            <div id="celebWinnerContainer" style="display: none;">
                <span class="celebration-winner-badge" id="celebWinnerName">PEMENANG</span>
            </div>
            <!-- Timer Bar Otomatis 5 Detik -->
            <div class="celebration-timer-container">
                <div class="celebration-timer-bar" id="celebTimerBar"></div>
            </div>
            <div class="celebration-close-hint">
                <span class="celebration-timer-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7v5l3 3" /></svg>
                    Menutup otomatis dalam <strong id="celebTimerSec">3</strong>s
                </span>
                <span class="celebration-manual-hint">• Klik di mana saja atau tekan ESC untuk menutup</span>
            </div>
        </div>
    </div>

    <!-- ========================================================
         KONTROL MENGAMBANG (SHARED)
         ======================================================== -->
    <div class="tv-controls-bar">
        <!-- Tombol Beralih Mode Layar TV (Kuis <-> Hadiah) -->
        <button type="button" class="tv-ctrl-btn" id="btnToggleStageMode" title="Klik untuk beralih antara Papan Kuis dan Kotak Hadiah">
            <span id="stageModeIcon" class="d-inline-flex align-items-center">
                @if (($tvMode ?? 'quiz') === 'gift')
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>
                @endif
            </span>
            <span id="stageModeText">{{ ($tvMode ?? 'quiz') === 'gift' ? 'Mode Kuis' : 'Mode Hadiah' }}</span>
        </button>

        <!-- Tombol Suara TV -->
        <button type="button" class="tv-ctrl-btn" id="btnTvSound" title="Klik untuk mengaktifkan / mematikan suara">
            <span id="soundIcon" class="d-inline-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8a5 5 0 0 1 0 8" /><path d="M17.7 5a9 9 0 0 1 0 14" /><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /></svg>
            </span>
            <span id="soundText">Suara Aktif</span>
        </button>

        <!-- Tombol Fullscreen -->
        <button type="button" class="tv-ctrl-btn" id="btnFullscreen" title="Mode Layar Penuh">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 8v-2a2 2 0 0 1 2 -2h2" /><path d="M4 16v2a2 2 0 0 0 2 2h2" /><path d="M16 4h2a2 2 0 0 1 2 2v2" /><path d="M16 20h2a2 2 0 0 0 2 -2v-2" /></svg>
            <span>Layar Penuh</span>
        </button>
    </div>

    <script>
        const stateUrl = @js(route('family-100.tv.state'));
        const switchModeUrl = @js(route('family-100.tv.mode'));
        const csrfToken = '{{ csrf_token() }}';

        let currentTvMode = @js($tvMode ?? 'quiz');
        let currentQuestionId = {{ $question?->id ?? 'null' }};
        let wrongSeen = {{ $question?->wrong_count ?? 0 }};
        let soundOn = true;

        const stageQuizWrapper = document.getElementById('stageQuizWrapper');
        const stageGiftWrapper = document.getElementById('stageGiftWrapper');
        const screenContainer = document.querySelector('.tv-screen-container');
        const strikesContainer = document.querySelector('[data-tv-strikes]');
        const strikeEl = document.querySelector('[data-tv-strike]');
        const modalCeleb = document.getElementById('modalCelebration');

        let openedGiftIds = new Set(@json($gifts->where('is_opened', true)->pluck('id')));
        let currentDisplayedStrikes = -1;
        let strikeTimeout = null;

        const strikeMarkSvg = `<span class="tv-strike-mark"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"><path d="M5 5l14 14M19 5L5 19"/></svg></span>`;

        // ========================================================
        // BULLETPROOF MULTI-LAYER AUDIO ENGINE
        // ========================================================
        let audioCtx = null;
        let correctBuffer = null;
        let wrongBuffer = null;
        let isAudioUnlocked = false;
        let keepAliveStarted = false;

        // Pre-allocated Audio Pool (3 instances each) to eliminate latency & avoid cloneNode issues
        const correctPool = [
            new Audio('{{ asset("sounds/correct.mp3") }}'),
            new Audio('{{ asset("sounds/correct.mp3") }}'),
            new Audio('{{ asset("sounds/correct.mp3") }}')
        ];
        const wrongPool = [
            new Audio('{{ asset("sounds/wrong.mp3") }}'),
            new Audio('{{ asset("sounds/wrong.mp3") }}'),
            new Audio('{{ asset("sounds/wrong.mp3") }}')
        ];
        let correctPoolIdx = 0;
        let wrongPoolIdx = 0;

        [...correctPool, ...wrongPool].forEach(a => {
            a.preload = 'auto';
            a.volume = 1.0;
        });

        function getAudioContext() {
            if (!audioCtx) {
                const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
                audioCtx = new AudioCtxClass();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume().catch(() => {});
            }
            if (!keepAliveStarted && audioCtx.state === 'running') {
                startKeepAliveNode(audioCtx);
            }
            return audioCtx;
        }

        function startKeepAliveNode(ctx) {
            try {
                // Silent keep-alive node prevents browser from auto-suspending audio hardware during idle
                const silentGain = ctx.createGain();
                silentGain.gain.value = 0.00001;
                const osc = ctx.createOscillator();
                osc.type = 'sine';
                osc.frequency.value = 440;
                osc.connect(silentGain);
                silentGain.connect(ctx.destination);
                osc.start();
                keepAliveStarted = true;
            } catch (e) {}
        }

        // Preload array buffers IMMEDIATELY upon page load
        async function initAudioPreload() {
            try {
                const [cRes, wRes] = await Promise.all([
                    fetch('{{ asset("sounds/correct.mp3") }}'),
                    fetch('{{ asset("sounds/wrong.mp3") }}')
                ]);
                const [cData, wData] = await Promise.all([
                    cRes.arrayBuffer(),
                    wRes.arrayBuffer()
                ]);

                // Try decoding once context is instantiated
                const ctx = getAudioContext();
                ctx.decodeAudioData(cData.slice(0), buf => { correctBuffer = buf; }, () => {});
                ctx.decodeAudioData(wData.slice(0), buf => { wrongBuffer = buf; }, () => {});
            } catch (err) {
                console.warn('Audio fetch preloading warning:', err);
            }
        }
        initAudioPreload();

        // Procedural Audio Synthesizer Fallbacks (Zero network dependency - 100% Guaranteed sound)
        function synthCorrectSound() {
            try {
                const ctx = getAudioContext();
                const now = ctx.currentTime;
                // Bright Family 100 Ding: E5 (659Hz) & C6 (1046Hz) bell harmonic
                [659.25, 1046.50].forEach((freq, i) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(freq, now + i * 0.04);
                    gain.gain.setValueAtTime(0.4, now + i * 0.04);
                    gain.gain.exponentialRampToValueAtTime(0.0001, now + i * 0.04 + 0.85);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now + i * 0.04);
                    osc.stop(now + i * 0.04 + 0.85);
                });
            } catch (e) {}
        }

        function synthWrongSound() {
            try {
                const ctx = getAudioContext();
                const now = ctx.currentTime;
                // Harsh Family 100 Buzzer Strike: Dual detuned low sawtooth buzz
                [130, 154].forEach(freq => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(freq, now);
                    gain.gain.setValueAtTime(0.45, now);
                    gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.55);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.55);
                });
            } catch (e) {}
        }

        async function unlockAudioEngine() {
            try {
                const ctx = getAudioContext();
                if (ctx.state === 'suspended') {
                    await ctx.resume();
                }
                startKeepAliveNode(ctx);
                isAudioUnlocked = true;

                const banner = document.getElementById('audioUnlockBanner');
                if (banner) banner.style.display = 'none';

                // Prime the audio pools
                correctPool.forEach(a => { a.load(); });
                wrongPool.forEach(a => { a.load(); });

                if (!correctBuffer || !wrongBuffer) {
                    initAudioPreload();
                }
            } catch (e) {}
        }

        window.addEventListener('pointerdown', unlockAudioEngine, { once: true });
        window.addEventListener('keydown', unlockAudioEngine, { once: true });
        window.addEventListener('click', unlockAudioEngine);

        const bannerPrompt = document.getElementById('audioUnlockBanner');
        if (bannerPrompt) {
            bannerPrompt.addEventListener('click', (e) => {
                e.stopPropagation();
                unlockAudioEngine();
                // Play pleasant subtle tone to confirm unlock
                synthCorrectSound();
            });
        }

        async function playBufferSound(buffer, pool, synthFallback) {
            if (!soundOn) return;

            let played = false;
            try {
                const ctx = getAudioContext();
                if (ctx.state === 'suspended') {
                    await ctx.resume();
                }
                if (buffer) {
                    const src = ctx.createBufferSource();
                    const gain = ctx.createGain();
                    gain.gain.value = 1.0;
                    src.buffer = buffer;
                    src.connect(gain);
                    gain.connect(ctx.destination);
                    src.start(0);
                    played = true;
                    return;
                }
            } catch (e) {}

            // Layer 2: Pre-allocated HTML5 Audio pool
            if (Array.isArray(pool) && pool.length > 0) {
                try {
                    const poolIndex = (pool === correctPool ? correctPoolIdx++ : wrongPoolIdx++) % pool.length;
                    const audio = pool[poolIndex];
                    audio.currentTime = 0;
                    const p = audio.play();
                    if (p !== undefined) {
                        p.then(() => { played = true; }).catch(() => {});
                    }
                } catch (e) {}
            }

            // Layer 3: Procedural Synthesizer Fallback (Guaranteed to play if Web Audio active)
            if (!played && typeof synthFallback === 'function') {
                synthFallback();
            }
        }

        function playCorrect() {
            playBufferSound(correctBuffer, correctPool, synthCorrectSound);
        }

        function playWrong() {
            playBufferSound(wrongBuffer, wrongPool, synthWrongSound);
        }

        // Chime Suara Reveal Hadiah
        function playChimeSound() {
            if (!soundOn) return;
            try {
                const ctx = getAudioContext();
                const now = ctx.currentTime;
                const notes = [523.25, 659.25, 783.99, 1046.50, 1318.51, 1567.98];
                notes.forEach((freq, idx) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(freq, now + idx * 0.08);

                    gain.gain.setValueAtTime(0, now + idx * 0.08);
                    gain.gain.linearRampToValueAtTime(0.3, now + idx * 0.08 + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.08 + 0.9);

                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now + idx * 0.08);
                    osc.stop(now + idx * 0.08 + 0.9);
                });
            } catch (e) {}
        }

        // ========================================================
        // KUIS: RENDERING & STRIKE MARKS
        // ========================================================
        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>"']/g, (m) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[m]));
        }

        function renderBoard(data) {
            if (!screenContainer) return;

            if (!data.question_id || !data.answers || data.answers.length === 0) {
                screenContainer.innerHTML = '<div class="tv-empty-state">Menunggu babak kuis dimulai...</div>';
                return;
            }

            const isDense = data.answers.length >= 8;
            const slotsHtml = data.answers.map((a, idx) => `
                <div class="tv-slot ${a.is_answered ? 'revealed' : ''}" data-slot="${a.id}">
                    <div class="tv-card-inner">
                        <div class="tv-card-front">
                            <span class="tv-rank">${idx + 1}</span>
                            <div class="tv-shutter"></div>
                            <span class="tv-empty-badge"></span>
                        </div>
                        <div class="tv-card-back">
                            <span class="tv-rank">${idx + 1}</span>
                            <span class="tv-answer-text">${escapeHtml(a.answer)}</span>
                            <span class="tv-answer-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5l10 -10"/></svg></span>
                        </div>
                    </div>
                </div>
            `).join('');

            screenContainer.innerHTML = `
                <div class="tv-board ${isDense ? 'tv-board-dense' : ''}">
                    ${slotsHtml}
                </div>
            `;
        }

        function updateStrikes(count) {
            if (!strikesContainer) return;
            const num = Math.max(0, Number(count) || 0);

            if (num <= 0) {
                strikesContainer.style.display = 'none';
                strikesContainer.innerHTML = '';
                currentDisplayedStrikes = 0;
                return;
            }

            if (num === currentDisplayedStrikes) return;
            currentDisplayedStrikes = num;

            strikesContainer.style.display = 'flex';
            const displayCount = Math.min(num, 10);
            let html = '';
            for (let i = 0; i < displayCount; i++) {
                html += strikeMarkSvg;
            }
            if (num > 10) {
                html += `<span class="tv-strike-mark-extra">+${num - 10}</span>`;
            }
            strikesContainer.innerHTML = html;
        }

        function strike() {
            strikeEl.classList.add('show');
            clearTimeout(strikeTimeout);
            strikeTimeout = setTimeout(() => strikeEl.classList.remove('show'), 1800);
            playWrong();
        }

        updateStrikes({{ $question?->wrong_count ?? 0 }});

        // ========================================================
        // HADIAH: REVEAL BOXES & CELEBRATION MODAL
        // ========================================================
        function launchCelebrationConfetti() {
            if (typeof confetti !== 'function') return;
            confetti({
                particleCount: 120,
                spread: 90,
                origin: { y: 0.6 },
                colors: ['#facc15', '#fbbf24', '#f59e0b', '#34d399', '#ffffff']
            });
            setTimeout(() => {
                confetti({
                    particleCount: 80,
                    angle: 60,
                    spread: 65,
                    origin: { x: 0, y: 0.65 },
                    colors: ['#ffd700', '#f59e0b', '#10b981']
                });
                confetti({
                    particleCount: 80,
                    angle: 120,
                    spread: 65,
                    origin: { x: 1, y: 0.65 },
                    colors: ['#ffd700', '#f59e0b', '#10b981']
                });
            }, 250);
        }

        let celebrationAutoCloseTimer = null;
        let celebrationCountdownInterval = null;
        const CELEBRATION_DURATION_MS = 3000;

        function showCelebrationModal(number, name, desc, winner) {
            // Bersihkan timer lama jika masih aktif
            if (celebrationAutoCloseTimer) {
                clearTimeout(celebrationAutoCloseTimer);
                celebrationAutoCloseTimer = null;
            }
            if (celebrationCountdownInterval) {
                clearInterval(celebrationCountdownInterval);
                celebrationCountdownInterval = null;
            }

            document.getElementById('celebBoxNumber').textContent = `KOTAK HADIAH #${number}`;
            document.getElementById('celebGiftName').textContent = name || `Hadiah #${number}`;
            document.getElementById('celebGiftDesc').textContent = desc || '';

            const winnerCont = document.getElementById('celebWinnerContainer');
            if (winner) {
                document.getElementById('celebWinnerName').textContent = `Pemenang: ${winner}`;
                winnerCont.style.display = 'block';
            } else {
                winnerCont.style.display = 'none';
            }

            // Reset visual bar timer ke 100% dan teks countdown ke 5s
            const timerBar = document.getElementById('celebTimerBar');
            const timerSec = document.getElementById('celebTimerSec');
            if (timerBar) {
                timerBar.style.transition = 'none';
                timerBar.style.width = '100%';
            }
            if (timerSec) {
                timerSec.textContent = '5';
            }

            modalCeleb.classList.add('show');
            launchCelebrationConfetti();
            playChimeSound();

            // Mulai animasi smooth mengecil selama 5 detik
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    if (timerBar && modalCeleb.classList.contains('show')) {
                        timerBar.style.transition = `width ${CELEBRATION_DURATION_MS}ms linear`;
                        timerBar.style.width = '0%';
                    }
                });
            });

            // Hitung mundur angka detik (5, 4, 3, 2, 1)
            const startTime = Date.now();
            const endTime = startTime + CELEBRATION_DURATION_MS;
            celebrationCountdownInterval = setInterval(() => {
                const remaining = Math.max(0, Math.ceil((endTime - Date.now()) / 1000));
                if (timerSec) {
                    timerSec.textContent = remaining;
                }
                if (remaining <= 0) {
                    clearInterval(celebrationCountdownInterval);
                    celebrationCountdownInterval = null;
                }
            }, 200);

            // Menutup otomatis setelah 5 detik
            celebrationAutoCloseTimer = setTimeout(() => {
                closeCelebrationModal();
            }, CELEBRATION_DURATION_MS);
        }

        function closeCelebrationModal() {
            if (celebrationAutoCloseTimer) {
                clearTimeout(celebrationAutoCloseTimer);
                celebrationAutoCloseTimer = null;
            }
            if (celebrationCountdownInterval) {
                clearInterval(celebrationCountdownInterval);
                celebrationCountdownInterval = null;
            }
            modalCeleb.classList.remove('show');
        }

        modalCeleb.addEventListener('click', closeCelebrationModal);
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeCelebrationModal();
        });

        function revealBox(box, number, name, desc, winner) {
            box.classList.add('opened');
            const openedContent = box.querySelector('.gift-opened-content');
            if (openedContent) {
                openedContent.innerHTML = `
                    <span class="opened-box-number">Kotak #${number}</span>
                    <div class="opened-box-name">${escapeHtml(name)}</div>
                    ${winner ? `<div class="opened-box-winner">${escapeHtml(winner)}</div>` : ''}
                `;
            }
        }

        // Buka kotak hadiah dengan klik langsung di layar TV
        function attachGiftBoxClick(box) {
            box.addEventListener('click', async () => {
                if (box.classList.contains('opened')) return;
                const giftId = Number(box.dataset.giftId);
                const number = box.dataset.number;
                const name = box.dataset.name;
                const desc = box.dataset.description;
                const winner = box.dataset.winner;

                revealBox(box, number, name, desc, winner);
                showCelebrationModal(number, name, desc, winner);

                try {
                    await fetch(box.dataset.openUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });
                    openedGiftIds.add(giftId);
                } catch (e) {}
            });
        }

        document.querySelectorAll('.gift-box-item').forEach(attachGiftBoxClick);

        function renderGiftGrid(gifts) {
            const grid = document.getElementById('tvGiftGrid');
            if (!grid) return;
            const giftCount = gifts.length;
            grid.classList.toggle('cols-6', giftCount > 20);

            grid.innerHTML = gifts.map(gift => `
                <div class="gift-box-item ${gift.is_opened ? 'opened' : ''}" data-gift-id="${gift.id}" data-number="${gift.number}" data-name="${escapeHtml(gift.name)}" data-description="${escapeHtml(gift.description || '')}" data-winner="${escapeHtml(gift.winner_name || '')}" data-open-url="/family-100/hadiah/${gift.id}/open">
                    <div class="gift-box-card">
                        <div class="gift-ribbon-v"></div>
                        <div class="gift-ribbon-h"></div>
                        <div class="gift-bow">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" width="36" height="36">
                                <defs>
                                    <linearGradient id="bowGoldGrad_${gift.id}" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#fff8ed" />
                                        <stop offset="35%" stop-color="#fde68a" />
                                        <stop offset="70%" stop-color="#d4af37" />
                                        <stop offset="100%" stop-color="#92400e" />
                                    </linearGradient>
                                </defs>
                                <path d="M18 18 C12 10, 4 12, 6 18 C8 24, 15 20, 18 18 Z" fill="url(#bowGoldGrad_${gift.id})" />
                                <path d="M18 18 C24 10, 32 12, 30 18 C28 24, 21 20, 18 18 Z" fill="url(#bowGoldGrad_${gift.id})" />
                                <path d="M16 19 L11 28 L14 26 L17 29 Z" fill="#92400e" />
                                <path d="M20 19 L25 28 L22 26 L19 29 Z" fill="#92400e" />
                                <circle cx="18" cy="18" r="3.2" fill="#fff8ed" stroke="#92400e" stroke-width="1" />
                            </svg>
                        </div>
                        <div class="gift-number-badge">${gift.number}</div>
                        <div class="gift-tap-hint">Pilih Kotak</div>
                        <div class="gift-opened-content">
                            <span class="opened-box-number">Kotak #${gift.number}</span>
                            <div class="opened-box-name">${escapeHtml(gift.name)}</div>
                            ${gift.winner_name ? `<div class="opened-box-winner">${escapeHtml(gift.winner_name)}</div>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');

            grid.querySelectorAll('.gift-box-item').forEach(attachGiftBoxClick);
        }

        const svgTvSmall = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>`;
        const svgGiftSmall = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>`;
        const svgVolOn = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8a5 5 0 0 1 0 8" /><path d="M17.7 5a9 9 0 0 1 0 14" /><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /></svg>`;
        const svgVolOff = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /><path d="M16 10l4 4m0 -4l-4 4" /></svg>`;

        // ========================================================
        // SINGLE-PAGE STAGE SWITCHER & REALTIME SYNC (Polling)
        // ========================================================
        function applyTvStageMode(mode) {
            currentTvMode = mode;
            if (mode === 'gift') {
                stageQuizWrapper.style.display = 'none';
                stageGiftWrapper.style.display = 'flex';
                const giftVideoEl = document.querySelector('.gift-bg-video');
                if (giftVideoEl) giftVideoEl.play().catch(() => {});
                document.getElementById('stageModeIcon').innerHTML = svgTvSmall;
                document.getElementById('stageModeText').textContent = 'Mode Kuis';
            } else {
                stageGiftWrapper.style.display = 'none';
                stageQuizWrapper.style.display = 'block';
                const quizVideoEl = document.querySelector('.tv-bg-video');
                if (quizVideoEl) quizVideoEl.play().catch(() => {});
                document.getElementById('stageModeIcon').innerHTML = svgGiftSmall;
                document.getElementById('stageModeText').textContent = 'Mode Hadiah';
            }
        }

        async function sync() {
            try {
                const response = await fetch(stateUrl, { headers: { 'Accept': 'application/json' } });
                const data = await response.json();

                // 1. Peralihan Mode Panggung Otomatis (Single Page - No Reload!)
                if (data.tv_mode && data.tv_mode !== currentTvMode) {
                    applyTvStageMode(data.tv_mode);
                }

                // 2. Sinkronisasi Kuis
                if (data.question_id && data.question_id !== currentQuestionId) {
                    currentQuestionId = data.question_id;
                    wrongSeen = data.wrong_count ?? 0;
                    renderBoard(data);
                    updateStrikes(wrongSeen);
                } else if (data.answers) {
                    const slots = document.querySelectorAll('[data-slot]');
                    let newlyRevealed = false;
                    slots.forEach((slot) => {
                        const reveal = (data.answered || []).includes(Number(slot.dataset.slot));
                        newlyRevealed ||= reveal && !slot.classList.contains('revealed');
                        slot.classList.toggle('revealed', reveal);
                    });
                    if (newlyRevealed) { playCorrect(); }
                    if (data.wrong_count > wrongSeen) { strike(); }
                    wrongSeen = data.wrong_count ?? 0;
                    updateStrikes(wrongSeen);
                }

                // 3. Sinkronisasi Hadiah
                const grid = document.getElementById('tvGiftGrid');
                if (grid && data.gift_count) {
                    grid.classList.toggle('cols-6', data.gift_count > 20);
                }

                const currentDomBoxCount = document.querySelectorAll('#tvGiftGrid .gift-box-item').length;
                if (data.gifts && data.gifts.length !== currentDomBoxCount) {
                    renderGiftGrid(data.gifts);
                } else {
                    (data.gifts || []).forEach(g => {
                        const box = document.querySelector(`[data-gift-id="${g.id}"]`);
                        if (!box) return;

                        box.dataset.name = g.name;
                        box.dataset.description = g.description || '';
                        box.dataset.winner = g.winner_name || '';

                        if (g.is_opened && !openedGiftIds.has(g.id)) {
                            openedGiftIds.add(g.id);
                            revealBox(box, g.number, g.name, g.description, g.winner_name);
                            showCelebrationModal(g.number, g.name, g.description, g.winner_name);
                        } else if (!g.is_opened && openedGiftIds.has(g.id)) {
                            openedGiftIds.delete(g.id);
                            box.classList.remove('opened');
                        } else if (g.is_opened) {
                            const nameEl = box.querySelector('.opened-box-name');
                            if (nameEl) nameEl.textContent = g.name;
                        }
                    });
                }
            } catch (error) {
                // Koneksi terputus sesaat: pertahankan tampilan terakhir
            }
        }

        setInterval(sync, 800);

        // ========================================================
        // KONTROL MENGAMBANG
        // ========================================================
        // Tombol Switch Mode Panggung (Bisa diklik dari TV langsung juga)
        const btnToggleMode = document.getElementById('btnToggleStageMode');
        btnToggleMode.addEventListener('click', async () => {
            const nextMode = currentTvMode === 'gift' ? 'quiz' : 'gift';
            applyTvStageMode(nextMode);
            try {
                await fetch(switchModeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ mode: nextMode })
                });
            } catch (e) {}
        });

        // Tombol Suara
        const soundBtn = document.getElementById('btnTvSound');
        soundBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            soundOn = !soundOn;
            document.getElementById('soundIcon').innerHTML = soundOn ? svgVolOn : svgVolOff;
            document.getElementById('soundText').textContent = soundOn ? 'Suara Aktif' : 'Suara Mati';
            soundBtn.style.opacity = soundOn ? '1' : '0.6';
            if (soundOn) {
                unlockAudioEngine();
                playCorrect();
            }
        });

        // Tombol Fullscreen
        const fsBtn = document.getElementById('btnFullscreen');
        fsBtn.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        });

        // Shortcut keyboard testing (X: salah, C: benar)
        window.addEventListener('keydown', (e) => {
            if (e.target && ['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;
            if (e.key === 'x' || e.key === 'X') {
                strike();
            } else if (e.key === 'c' || e.key === 'C') {
                playCorrect();
            }
        });

        // Video background: selalu muted & looping
        const bgVideo = document.querySelector('.tv-bg-video');
        if (bgVideo) {
            bgVideo.muted = true;
            bgVideo.defaultMuted = true;
            bgVideo.loop = true;
            const startBgVideo = () => {
                bgVideo.muted = true;
                bgVideo.play().catch(() => {});
            };
            startBgVideo();
            window.addEventListener('click', startBgVideo, { once: true });
            window.addEventListener('keydown', startBgVideo, { once: true });
        }

        // Video background Hadiah TV: selalu muted & looping
        const giftBgVideo = document.querySelector('.gift-bg-video');
        if (giftBgVideo) {
            giftBgVideo.muted = true;
            giftBgVideo.defaultMuted = true;
            giftBgVideo.loop = true;
            const startGiftBgVideo = () => {
                giftBgVideo.muted = true;
                giftBgVideo.play().catch(() => {});
            };
            startGiftBgVideo();
            window.addEventListener('click', startGiftBgVideo, { once: true });
            window.addEventListener('keydown', startGiftBgVideo, { once: true });
        }
    </script>
</body>
</html>
