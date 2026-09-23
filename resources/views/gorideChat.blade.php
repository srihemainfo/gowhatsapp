<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Whatsapp</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="https://www.goride.net.in/public/goride/img/Go-Ride-fav-icon.webp">
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-firestore-compat.js"></script>

    <style>
        @keyframes spinIcon {
        100% { transform: rotate(360deg); }
        }
        .spin-anim {
            animation: spinIcon 1s linear infinite;
            color: var(--accent-green) !important;
        }
        :root {
            --app-bg: #d1d7db;
            --chat-bg: #efeae2;
            --sidebar-bg: #ffffff;
            --header-bg: #f0f2f5;
            --active-chat: #f0f2f5;
            --hover-chat: #f5f6f6;
            --incoming-msg: #ffffff;
            --outgoing-msg: #d9fdd3;
            --text-primary: #111b21;
            --text-secondary: #667781;
            --accent-green: #25d366;
            --border: #e9edef;
            --input-bg: #ffffff;
            --blue-tick: #007bff;
            --grey-tick: #54656f;
            --search-bg: #f0f2f5;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--app-bg);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .app-container {
            width: 100%;
            height: 100%;
            display: flex;
            background: var(--sidebar-bg);
            max-width: 1600px;
            position: relative;
        }

        .sidebar {
            width: 30%;
            min-width: 350px;
            display: flex;
            flex-direction: column;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            z-index: 2;
        }

        .header {
            height: 60px;
            min-height: 60px !important;
            flex-shrink: 0 !important;
            background: var(--header-bg);
            display: flex;
            align-items: center;
            padding: 0 16px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            width: 100%;
        }

        .brand-logo { height: 28px; width: auto; }

        .search-container {
            padding: 8px 12px;
            background: #fff;
        }

        .search-wrapper {
            background: var(--search-bg);
            border-radius: 8px;
            display: flex;
            align-items: center;
            padding: 0 12px;
        }

        .search-input {
            width: 100%;
            border: none;
            background: transparent;
            padding: 8px;
            font-size: 14px;
            outline: none;
            color: var(--text-primary);
        }

        .filter-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            padding: 0 12px 8px 12px;
            background: #fff;
            border-bottom: 1px solid var(--border);
        }

        .filter-btn {
            padding: 5px 14px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: var(--search-bg);
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 12.5px;
            font-weight: 500;
            transition: 0.2s;
        }
        
        .filter-btn:hover { background: var(--hover-chat); }
        .filter-btn.active { background: var(--text-secondary); color: #fff; border-color: var(--text-secondary); }
        .filter-btn.active-green { background: var(--accent-green); color: #fff; border-color: var(--accent-green); }

        .date-filter-wrapper { position: relative; font-family: inherit; }
        .date-filter-display { 
            display: flex; align-items: center; justify-content: space-between; gap: 4px;
            padding: 5px 10px; border-radius: 16px; border: 1px solid var(--border);
            background: var(--search-bg); color: var(--text-secondary); font-weight: 500;
            font-size: 12.5px; cursor: pointer; white-space: nowrap; transition: 0.2s;
        }
        .date-filter-display:hover { background: var(--hover-chat); }
        .date-filter-display.active { background: var(--text-secondary); color: #fff; border-color: var(--text-secondary); }
        
        .clear-date { 
            font-size: 14px; font-weight: bold; cursor: pointer; 
            padding: 0 4px; border-radius: 50%;
        }
        .clear-date:hover { background: rgba(0,0,0,0.1); color: #d32f2f; }
        .date-filter-display.active .clear-date { color: #fff; }
        .date-filter-display.active .clear-date:hover { background: rgba(255,255,255,0.2); color: #fff; }

        .date-filter-menu {
            position: absolute; top: calc(100% + 5px); right: 0; background: #fff;
            border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 4px 12px rgba(11,20,26,0.15);
            z-index: 100; min-width: 170px; max-height: calc(100vh - 160px); overflow-y: auto; overflow-x: hidden;
        }
        .preset-option { 
            padding: 10px 14px; font-size: 13.5px; color: var(--text-primary); 
            cursor: pointer; border-bottom: 1px solid var(--header-bg); 
        }
        .preset-option:hover { background: var(--hover-chat); }
        .preset-option:last-child { border-bottom: none; }
        
        .custom-date-section { 
            padding: 12px; display: flex; flex-direction: column; gap: 8px; 
            background: var(--search-bg); border-top: 1px solid var(--border);
        }
        .custom-date-section label { font-size: 11px; color: var(--text-secondary); font-weight: bold; text-transform: uppercase;}
        .custom-date-input { 
            width: 100%; border: 1px solid var(--border); padding: 6px; 
            border-radius: 4px; font-size: 12.5px; font-family: inherit; color: var(--text-primary);
        }
        .apply-custom-btn { 
            background: var(--accent-green); color: #fff; border: none; border-radius: 4px; 
            padding: 8px; cursor: pointer; font-size: 13px; font-weight: 500; margin-top: 4px;
        }
        .apply-custom-btn:hover { background: #1fa855; }

        .contact-list { flex: 1; overflow-y: auto; }

        .contact {
            display: flex;
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid var(--border);
            align-items: center;
            position: relative;
        }

        .contact:hover { background: var(--hover-chat); }
        .contact.active { background: var(--active-chat); }
        .contact-details { margin-left: 15px; flex: 1; overflow: hidden; }
        .contact-top { display: flex; justify-content: space-between; margin-bottom: 3px; align-items: center; }
        
        .contact-name { font-size: 16px; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex:1;}
        .contact-time { font-size: 12px; color: var(--text-secondary); white-space: nowrap; margin-left: 5px; }
        .contact-bottom { display: flex; justify-content: space-between; align-items: center; }
        .contact-last-msg { font-size: 13px; color: var(--text-secondary); overflow: hidden; white-space: nowrap; text-overflow: ellipsis; padding-right: 10px; flex:1;}

        .unread-badge {
            background-color: var(--accent-green);
            color: white;
            border-radius: 12px;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            padding: 0 6px;
        }
        
        .mention-badge {
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            font-size: 10px;
            padding: 2px 4px;
            margin-right: 5px;
            font-weight: bold;
        }

        .contact-more {
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px;
            border-radius: 50%;
            display: none;
            margin-left: 5px;
        }

        .contact:hover .contact-more { display: block; }
        .contact-more:hover { background: var(--border); color: var(--text-primary); }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--chat-bg);
            position: relative;
        }

        .chat-area-bg::before {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
            opacity: 0.06; pointer-events: none; z-index: 0;
        }

        .chat-header-info { display: flex; align-items: center; flex: 1; overflow: hidden; }
        .chat-header-text { margin-left: 15px; display: flex; flex-direction: column; overflow: hidden; }
        
        .chat-header-name-wrapper { display: flex; align-items: center; gap: 8px; }
        .chat-header-name { color: var(--text-primary); font-size: 16px; font-weight: 500; white-space: nowrap; text-overflow: ellipsis; }
        .edit-icon { color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; }
        .edit-icon:hover { color: var(--text-primary); }
        
        .chat-header-status { font-size: 13px; color: var(--text-secondary); }

        .profile-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .mobile-back { display: none; margin-right: 10px; cursor: pointer; color: #54656f; background: none; border: none; }

        .highlight { background-color: #ffeb3b; color: #000; }

        .messages-container { flex: 1; padding: 20px 8%; overflow-y: auto; display: flex; flex-direction: column; z-index: 1; scroll-behavior: smooth; }

        .date-divider {
            display: flex;
            justify-content: center;
            margin: 16px 0;
            z-index: 1;
        }

        .date-divider span {
            background-color: #ffffff;
            color: #54656f;
            font-size: 12.5px;
            padding: 6px 12px;
            border-radius: 8px;
            box-shadow: 0 1px 0.5px rgba(11,20,26,.13);
            text-transform: uppercase;
            display: inline-block;
        }

        .msg {
            max-width: 65%; padding: 6px 9px 8px 9px; margin-bottom: 12px; font-size: 14.2px;
            position: relative; color: var(--text-primary); line-height: 19px; display: inline-flex;
            flex-direction: column; word-wrap: break-word; box-shadow: 0 1px 0.5px rgba(11,20,26,.13);
            border-radius: 8px; transition: box-shadow 0.15s ease;
        }

        .msg:hover .msg-react-trigger {
            opacity: 1; pointer-events: auto;
        }

        .msg span { white-space: pre-wrap; word-break: break-word; }

        .msg-in { background: var(--incoming-msg); align-self: flex-start; border-top-left-radius: 0; }
        .msg-out { background: var(--outgoing-msg); align-self: flex-end; border-top-right-radius: 0; }

        /* Authentic WhatsApp Speech Bubble Tails */
        .msg-in::before {
            content: ""; position: absolute; top: 0; left: -8px; width: 8px; height: 13px;
            background: radial-gradient(circle at top left, transparent 8px, #ffffff 8.5px);
            pointer-events: none;
        }

        .msg-out::after {
            content: ""; position: absolute; top: 0; right: -8px; width: 8px; height: 13px;
            background: radial-gradient(circle at top right, transparent 8px, #d9fdd3 8.5px);
            pointer-events: none;
        }

        .msg-meta { display: flex; align-items: center; justify-content: flex-end; gap: 4px; margin-top: 3px; float: right; margin-left: 12px; }
        .msg-time { font-size: 11px; color: var(--text-secondary); white-space: nowrap; }
        .msg-status svg { width: 18px; height: 16px; margin-left: 2px; }

        /* Message Reactions Trigger & Pill */
        .msg-react-trigger {
            position: absolute; top: 4px; right: -26px; width: 26px; height: 26px;
            border-radius: 50%; background: #ffffff; border: 1px solid var(--border);
            box-shadow: 0 1px 4px rgba(0,0,0,0.15); color: #667781; display: flex;
            align-items: center; justify-content: center; cursor: pointer; opacity: 0;
            pointer-events: none; transition: opacity 0.2s ease, transform 0.15s ease; z-index: 20;
        }

        /* Invisible bridge to prevent mouseleave when moving cursor from message bubble to trigger */
        .msg-react-trigger::before {
            content: ""; position: absolute; top: -10px; bottom: -10px;
            left: -18px; right: -8px; z-index: -1;
        }

        .msg-out .msg-react-trigger { right: auto; left: -26px; }
        .msg-out .msg-react-trigger::before { left: -8px; right: -18px; }

        .msg:hover .msg-react-trigger,
        .msg-react-trigger:hover,
        .msg-react-trigger:focus,
        .msg-react-trigger:active {
            opacity: 1 !important;
            pointer-events: auto !important;
        }

        .msg-react-trigger:hover {
            color: #111b21;
            transform: scale(1.18);
            background: #f0f2f5;
        }

        .msg-reaction-pill {
            position: absolute; bottom: -10px; right: 8px; background: #ffffff;
            border: 1px solid #e9edef; box-shadow: 0 1px 3px rgba(11,20,26,0.18);
            border-radius: 12px; padding: 1px 6px; font-size: 13px; line-height: 18px;
            cursor: pointer; z-index: 5; user-select: none; transition: transform 0.15s ease;
            display: inline-flex; align-items: center;
        }

        .msg-in .msg-reaction-pill { right: auto; left: 8px; }
        .msg-reaction-pill:hover { transform: scale(1.2); }

        /* Clean WhatsApp Media Bubbles */
        .msg-media-bubble {
            padding: 3px !important;
            border-radius: 8px !important;
            overflow: hidden;
            display: inline-flex !important;
            flex-direction: column;
            width: fit-content !important;
            min-width: 220px !important;
            min-height: 160px !important;
            max-width: 330px;
            background: var(--incoming-msg);
            box-sizing: border-box;
        }

        .msg-out.msg-media-bubble {
            background: var(--outgoing-msg) !important;
        }

        .msg-media-captioned {
            padding: 3px 3px 6px 3px !important;
            min-height: auto !important;
        }

        .msg-media-bubble .media-container {
            margin-bottom: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
            min-width: 220px;
            position: relative;
        }

        .msg-media-bubble .media-rendered-content {
            position: relative;
            width: 100%;
            min-width: 220px;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            background: transparent;
            border-radius: 6px;
        }

        .msg-media-bubble .chat-media-img {
            min-width: 220px;
            min-height: 160px;
            max-width: 330px;
            max-height: 330px;
            width: 100%;
            height: auto;
            border-radius: 6px;
            display: block;
            object-fit: cover;
            background: #e9edef;
        }

        .msg-media-captioned .chat-media-img,
        .msg-media-captioned .media-video-container {
            border-radius: 6px 6px 0 0 !important;
        }

        .media-caption-box {
            display: flex;
            flex-direction: column;
            padding: 6px 7px 2px 7px;
            width: 100%;
            box-sizing: border-box;
        }

        .media-caption-text {
            font-size: 14.2px;
            line-height: 19px;
            color: var(--text-primary);
            word-break: break-word;
            user-select: text;
        }

        .media-caption-box .msg-meta {
            align-self: flex-end;
            margin: 2px 0 0 0 !important;
            float: right;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .msg-meta-floating {
            position: absolute;
            bottom: 7px;
            right: 7px;
            margin: 0 !important;
            float: none !important;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(11, 20, 26, 0.48);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border-radius: 12px;
            padding: 2px 7px;
            color: #ffffff !important;
            z-index: 5;
            pointer-events: none;
            user-select: none;
        }

        .msg-meta-floating .msg-time {
            color: #ffffff !important;
            font-size: 11px;
            font-weight: 500;
        }

        .msg-meta-floating .msg-status svg {
            width: 16px;
            height: 15px;
            margin-left: 2px;
        }

        .reaction-floating-bar {
            position: fixed; background: #ffffff; border-radius: 24px;
            box-shadow: 0 4px 18px rgba(11,20,26,0.22); padding: 4px 8px;
            display: flex; align-items: center; gap: 4px; z-index: 3000;
            border: 1px solid #e9edef; animation: reactPop 0.16s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes reactPop {
            0% { transform: scale(0.6); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .react-emoji {
            font-size: 24px; line-height: 1; padding: 4px 6px; border-radius: 50%;
            cursor: pointer; transition: transform 0.15s ease, background 0.15s ease; user-select: none;
        }

        .react-emoji:hover { transform: scale(1.35) translateY(-2px); background: #f0f2f5; }

        /* WhatsApp Media Message Styling */
        .media-container {
            display: flex; flex-direction: column; gap: 4px; min-width: 180px;
            max-width: 320px; margin-bottom: 2px;
        }

        .media-placeholder-card {
            background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.07);
            border-radius: 8px; padding: 10px 14px; display: flex; flex-direction: column;
            align-items: center; gap: 8px; text-align: center; width: 100%;
        }

        .msg-out .media-placeholder-card {
            background: rgba(0, 0, 0, 0.025); border-color: rgba(0, 0, 0, 0.06);
        }

        .media-placeholder-info {
            display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%;
        }

        .media-placeholder-icon { font-size: 22px; line-height: 1; }
        .media-placeholder-title { font-size: 13.5px; font-weight: 500; color: var(--text-primary); word-break: break-word; }

        .media-doc-name {
            font-size: 13px; font-weight: 600; color: var(--text-primary); word-break: break-all;
            max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }

        .media-actions {
            display: flex; align-items: center; justify-content: center; flex-wrap: wrap;
            gap: 8px; width: 100%; margin-top: 4px;
        }

        .media-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 6px 14px; font-size: 12.5px; font-weight: 500; border-radius: 16px;
            border: 1px solid var(--border); background: #ffffff; color: var(--text-primary);
            cursor: pointer; transition: all 0.18s ease; outline: none; user-select: none; font-family: inherit;
        }

        .media-btn:hover:not(:disabled) { background: var(--hover-chat); border-color: #c0c6c9; }
        .media-btn:disabled { opacity: 0.65; cursor: not-allowed; }

        .media-btn-primary {
            background: var(--accent-green); color: #ffffff; border-color: var(--accent-green);
        }

        .media-btn-primary:hover:not(:disabled) { background: #008069; border-color: #008069; }

        .media-btn-store {
            background: #f0f2f5; color: var(--text-secondary); border-color: #d1d7db;
        }

        .media-btn-store:hover:not(:disabled) { background: #e2e5e9; color: var(--text-primary); }

        .media-stored-badge {
            display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px;
            font-weight: 600; color: #166534; background: #dcfce7; border: 1px solid #bbf7d0;
            padding: 3px 9px; border-radius: 14px;
        }

        .media-error-text {
            font-size: 11.5px; color: #dc2626; margin-top: 4px; text-align: center;
            width: 100%; word-break: break-word;
        }

        .media-rendered-content {
            display: flex; flex-direction: column; gap: 6px; align-items: flex-start; width: 100%;
        }

        .chat-media-img {
            max-width: 100%; max-height: 280px; border-radius: 8px; object-fit: contain;
            cursor: pointer; display: block; box-shadow: 0 1px 2px rgba(0,0,0,0.12);
            transition: opacity 0.18s;
        }

        .chat-media-img:hover { opacity: 0.95; }

        .chat-media-sticker {
            max-width: 140px; max-height: 140px; object-fit: contain; display: block;
        }

        /* Fixed Video Container */
        .media-video-container {
            position: relative; min-width: 220px; min-height: 160px; max-width: 330px; max-height: 330px;
            width: 100%; height: auto; border-radius: 6px; overflow: hidden; background: #000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2); display: flex; align-items: center; justify-content: center;
        }

        .chat-media-video {
            min-width: 220px; min-height: 160px; max-width: 330px; max-height: 330px; width: 100%; height: auto;
            border-radius: 6px; display: block; background: #000; object-fit: contain;
        }

        .video-expand-btn {
            position: absolute; top: 8px; right: 8px; background: rgba(11, 20, 26, 0.65);
            color: #ffffff; border: none; border-radius: 50%; width: 28px; height: 28px;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            z-index: 5; transition: background 0.15s, transform 0.15s;
        }

        .video-expand-btn:hover { background: rgba(0, 168, 132, 0.95); transform: scale(1.1); }

        /* Voice Note Audio Player */
        .chat-vn-player {
            display: flex; align-items: center; gap: 10px; padding: 6px 10px;
            background: rgba(0, 0, 0, 0.035); border-radius: 14px; min-width: 220px; max-width: 280px;
        }

        .msg-out .chat-vn-player { background: rgba(0, 0, 0, 0.025); }

        .vn-play-circle {
            width: 38px; height: 38px; border-radius: 50%; background: var(--accent-green);
            color: #ffffff; border: none; display: flex; align-items: center; justify-content: center;
            cursor: pointer; flex-shrink: 0; transition: transform 0.15s, background 0.15s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.18);
        }

        .vn-play-circle:hover { background: #008069; transform: scale(1.06); }

        .vn-track-wrapper { flex: 1; display: flex; flex-direction: column; gap: 4px; cursor: pointer; }

        .vn-waveform {
            display: flex; align-items: center; gap: 2.5px; height: 20px; width: 100%;
        }

        .vn-bar {
            flex: 1; background: #8696a0; border-radius: 2px; transition: background 0.15s;
            min-height: 4px;
        }

        .vn-bar.played { background: var(--accent-green); }

        .vn-timeline {
            display: flex; justify-content: space-between; font-size: 11px;
            color: var(--text-secondary); font-weight: 500;
        }

        .media-caption {
            font-size: 13.5px; color: var(--text-primary); line-height: 18px;
            word-break: break-word; margin-top: 4px; padding: 0 2px;
        }

        .media-store-bar {
            display: flex; align-items: center; gap: 8px; margin-top: 4px;
        }

        /* Attachment Menu */
        .attach-popup-menu {
            position: absolute; bottom: 65px; left: 14px; background: #ffffff;
            border-radius: 12px; box-shadow: 0 4px 20px rgba(11,20,26,0.2);
            border: 1px solid var(--border); padding: 8px 0; z-index: 1000;
            display: flex; flex-direction: column; min-width: 190px; animation: fadeIn 0.15s ease-out;
        }

        .attach-menu-item {
            display: flex; align-items: center; gap: 12px; padding: 10px 16px;
            cursor: pointer; transition: background 0.15s; font-size: 14px; color: var(--text-primary);
        }

        .attach-menu-item:hover { background: var(--hover-chat); }

        .attach-icon-circle {
            width: 32px; height: 32px; border-radius: 50%; display: flex;
            align-items: center; justify-content: center; flex-shrink: 0;
        }

        /* Media Send Preview Modal */
        .media-send-modal-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(11, 20, 26, 0.88); z-index: 2500; flex-direction: column;
            justify-content: space-between; padding: 18px; backdrop-filter: blur(4px);
        }

        .media-send-header {
            display: flex; justify-content: space-between; align-items: center; color: #ffffff;
            padding: 0 10px; height: 44px;
        }

        .media-send-close-btn {
            background: none; border: none; color: #ffffff; font-size: 28px; cursor: pointer; line-height: 1;
        }

        .media-send-body {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 16px; overflow: hidden; max-height: calc(100vh - 170px);
        }

        .media-send-preview-img {
            max-width: 90vw; max-height: 55vh; border-radius: 8px; object-fit: contain;
            box-shadow: 0 4px 18px rgba(0,0,0,0.5);
        }

        .media-send-preview-video {
            max-width: 90vw; max-height: 55vh; border-radius: 8px; background: #000;
        }

        .media-send-doc-box {
            background: #ffffff; border-radius: 12px; padding: 24px 32px; display: flex;
            flex-direction: column; align-items: center; gap: 10px; color: var(--text-primary);
            text-align: center; max-width: 400px;
        }

        .media-send-footer {
            display: flex; align-items: center; gap: 12px; max-width: 800px; width: 100%;
            margin: 0 auto; background: #202c33; border-radius: 28px; padding: 6px 14px;
        }

        .media-send-caption-input {
            flex: 1; background: transparent; border: none; outline: none;
            color: #ffffff; font-size: 15px; font-family: inherit; padding: 8px 4px;
        }

        .media-send-caption-input::placeholder { color: #8696a0; }

        .media-send-btn {
            width: 42px; height: 42px; border-radius: 50%; background: #00a884;
            color: #ffffff; border: none; display: flex; align-items: center;
            justify-content: center; cursor: pointer; transition: transform 0.15s, background 0.15s;
            flex-shrink: 0;
        }

        .media-send-btn:hover { background: #02906f; transform: scale(1.08); }

        .lightbox-btn {
            background: rgba(11, 20, 26, 0.75); color: #ffffff; border: none;
            width: 38px; height: 38px; border-radius: 50%; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.15s; font-size: 22px; text-decoration: none;
        }

        .lightbox-btn:hover { background: #00a884; transform: scale(1.1); }

        .send-btn-round, #micBtn, #sendBtn {
            width: 42px !important; height: 42px !important; min-width: 42px !important; min-height: 42px !important;
            border-radius: 50% !important; background: #00a884 !important;
            color: #ffffff !important; border: none !important; align-items: center !important; justify-content: center !important;
            cursor: pointer !important; transition: background 0.15s, transform 0.15s !important; flex-shrink: 0 !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2) !important;
        }

        .send-btn-round:hover, #micBtn:hover, #sendBtn:hover {
            background: #008069 !important; transform: scale(1.08) !important;
        }

        #micBtn svg, #sendBtn svg {
            color: #ffffff !important; fill: #ffffff !important;
        }

        .footer { min-height: 62px; background: var(--header-bg); display: flex; align-items: center; padding: 10px 16px; z-index: 2; gap: 10px; }
        .input-box { flex: 1; background: var(--input-bg); border-radius: 8px; padding: 12px 16px; border: none; outline: none; font-size: 15px; }
        .icon-btn { background: none; border: none; color: #54656f; cursor: pointer; display: flex; align-items: center; }

        .attach-btn-icon {
            padding: 8px; border-radius: 50%; color: #54656f;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.15s, color 0.15s;
        }
        .attach-btn-icon:hover {
            background: rgba(11, 20, 26, 0.08); color: #111b21;
        }

        /* Voice Recording Bar & Animations */
        .voice-record-bar {
            display: none; flex: 1; align-items: center; justify-content: space-between;
            background: var(--input-bg); border-radius: 8px; padding: 10px 16px; gap: 14px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08); animation: fadeIn 0.15s ease-out;
        }

        .voice-rec-dot {
            width: 11px; height: 11px; border-radius: 50%; background: #ef4444;
            display: inline-block; animation: voicePulse 1s infinite alternate;
        }

        @keyframes voicePulse {
            0% { transform: scale(0.85); opacity: 0.45; }
            100% { transform: scale(1.25); opacity: 1; }
        }

        .voice-rec-timer {
            font-size: 14.5px; font-weight: 500; color: var(--text-primary);
            font-variant-numeric: tabular-nums;
        }

        .voice-rec-waves {
            display: flex; align-items: center; gap: 3.5px; flex: 1;
            max-width: 120px; justify-content: center; height: 22px;
        }

        .voice-rec-waves span {
            width: 3px; height: 14px; background: #8696a0; border-radius: 2px;
            animation: waveBounce 1.2s infinite ease-in-out;
        }
        .voice-rec-waves span:nth-child(2) { animation-delay: 0.15s; height: 18px; }
        .voice-rec-waves span:nth-child(3) { animation-delay: 0.3s; height: 12px; }
        .voice-rec-waves span:nth-child(4) { animation-delay: 0.45s; height: 22px; }
        .voice-rec-waves span:nth-child(5) { animation-delay: 0.6s; height: 16px; }
        .voice-rec-waves span:nth-child(6) { animation-delay: 0.75s; height: 20px; }
        .voice-rec-waves span:nth-child(7) { animation-delay: 0.9s; height: 11px; }

        @keyframes waveBounce {
            0%, 100% { transform: scaleY(0.4); opacity: 0.35; }
            50% { transform: scaleY(1); opacity: 1; background: var(--accent-green); }
        }

        .voice-rec-cancel-btn {
            background: none; border: none; color: #ef4444; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            padding: 6px; border-radius: 50%; transition: background 0.15s;
        }
        .voice-rec-cancel-btn:hover { background: rgba(239, 68, 68, 0.12); }

        .default-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            color: #667781;
            background-color: var(--header-bg);
            z-index: 10;
        }

        .active-chat-screen {
            display: none;
            flex-direction: column;
            height: 100%;
            width: 100%;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(11, 20, 26, 0.4);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.2s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 17px 50px 0 rgba(11,20,26,.19), 0 12px 15px 0 rgba(11,20,26,.24);
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }

        .modal-title { font-size: 16px; color: var(--text-primary); font-weight: 500; margin-bottom: 20px; }

        .modal-input {
            width: 100%; border: none; border-bottom: 2px solid var(--accent-green);
            padding: 8px 0; font-size: 15px; outline: none; margin-bottom: 24px;
            color: var(--text-primary); background: transparent;
        }

        .modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: auto; padding-top: 20px; }

        .modal-btn { border: none; padding: 10px 24px; border-radius: 24px; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.2s; }
        .cancel-btn { background: transparent; color: var(--accent-green); border: 1px solid var(--border); }
        .cancel-btn:hover { background: var(--hover-chat); }
        .save-btn { background: var(--accent-green); color: white; }
        .save-btn:hover { background: #1fa855; }
        
        .template-item { text-align: left; width: 100%; border: 1px solid #ccc; padding: 12px; border-radius: 8px; margin-bottom: 8px; cursor: pointer; background: #fff; font-size: 14px; color: var(--text-primary); transition: 0.2s; }
        .template-item:hover { background: var(--hover-chat); border-color: var(--accent-green); }
        .template-list-container { overflow-y: auto; flex: 1; }

        .context-menu {
            position: absolute;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(11,20,26,.15);
            border-radius: 4px;
            z-index: 2000;
            padding: 8px 0;
            min-width: 160px;
        }
        .context-menu-item {
            padding: 12px 16px;
            cursor: pointer;
            font-size: 14.5px;
            color: var(--text-primary);
            transition: background 0.2s;
        }
        .context-menu-item:hover {
            background: var(--hover-chat);
        }

        .spinner {
            border: 3px solid rgba(0, 0, 0, 0.1);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border-left-color: var(--accent-green);
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            body { height: 100dvh; overflow: hidden; }
            .app-container { height: 100dvh; overflow: hidden; }
            .sidebar { width: 100%; min-width: 100%; height: 100dvh; }
            .chat-area { display: none; width: 100%; height: 100dvh; overflow: hidden; }
            .mobile-back { display: flex !important; align-items: center; justify-content: center; margin-right: 8px; cursor: pointer; }
            .app-container.show-chat .sidebar { display: none !important; }
            .app-container.show-chat .chat-area { display: flex !important; }
            .active-chat-screen { height: 100dvh; overflow: hidden; }
            .active-chat-screen .header { min-height: 60px !important; max-height: 60px !important; flex-shrink: 0 !important; }
            .active-chat-screen .header .search-wrapper { display: none !important; }
            .messages-container { padding: 12px 10px; }
            .chat-header-text { margin-left: 10px; }
        }
    </style>
</head>
<body>

<div class="app-container" id="appContainer">
    <div class="sidebar" id="sidebar">
        <div class="header">
            <img src="https://www.goride.run/goride/img/logo-light.png" class="brand-logo" alt="GoRide" referrerpolicy="no-referrer">
            <a href="{{ route('chat.logout') }}" style="margin-left: auto; color: #54656f;">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M16 17v-3H9v-4h7V7l5 5-5 5M14 2a2 2 0 012 2v2h-2V4H5v16h9v-2h2v2a2 2 0 01-2 2H5a2 2 0 01-2-2V4a2 2 0 012-2h9z"></path></svg>
            </a>
        </div>
        
        <div class="search-container">
            <div class="search-wrapper">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="#54656f"><path d="M15.009 13.805h-.636l-.22-.219a5.184 5.184 0 0 0 1.256-3.386 5.207 5.207 0 1 0-5.207 5.208 5.183 5.183 0 0 0 3.385-1.255l.221.22v.635l4.004 3.999 1.194-1.195-3.997-4.007zm-4.808 0a3.605 3.605 0 1 1 0-7.21 3.605 3.605 0 0 1 0 7.21z"></path></svg>
                <input type="text" class="search-input" id="sidebarSearch" placeholder="Search by name or phone" onkeyup="filterContacts()">
            </div>
        </div>
        
        <div class="filter-container">
            <button class="filter-btn active" id="btnFilterAll" onclick="setFilter('all')">All</button>
            <button class="filter-btn" id="btnFilterUnread" onclick="setFilter('unread')">Unread</button>
            
            <div class="date-filter-wrapper" id="mentionFilterWrapperId">
                <div class="date-filter-display" id="mentionFilterDisplayId" onclick="toggleMentionMenu()">
                    <span id="mentionFilterText">@ Mentions</span>
                    <span class="clear-date" onclick="clearMentionFilter(event)" id="clearMentionBtn" style="display:none;" title="Clear Mention">&times;</span>
                </div>
                <div class="date-filter-menu" id="mentionFilterMenu" style="display:none; left:0; right:auto;">
                    <div id="mentionFilterAdminList"><div class="preset-option">Loading...</div></div>
                </div>
            </div>

            <div class="date-filter-wrapper" id="dateFilterWrapperId" style="margin-left: auto;">
                <div class="date-filter-display" id="dateFilterDisplayId" onclick="toggleDateMenu()">
                    <span id="dateFilterText">All Dates</span>
                    <span class="clear-date" onclick="clearDateFilter(event)" id="clearDateBtn" style="display:none;" title="Clear Date">&times;</span>
                </div>
                <div class="date-filter-menu" id="dateFilterMenu" style="display:none;">
                    <div class="preset-option" onclick="applyDatePreset('today')">Today</div>
                    <div class="preset-option" onclick="applyDatePreset('yesterday')">Yesterday</div>
                    <div class="preset-option" onclick="applyDatePreset('last7')">Last 7 Days</div>
                    <div class="preset-option" onclick="applyDatePreset('thisMonth')">This Month</div>
                    <div class="preset-option" onclick="applyDatePreset('lastMonth')">Last Month</div>
                    <div class="preset-option" onclick="applyDatePreset('thisYear')">This Year</div>
                    <div class="preset-option" onclick="applyDatePreset('lastYear')">Last Year</div>
                    <div class="preset-option" onclick="toggleCustomDate()">Custom Range...</div>
                    
                    <div class="custom-date-section" id="customDateSection" style="display:none;">
                        <div>
                            <label>Start Date</label>
                            <input type="date" id="customStartDate" class="custom-date-input">
                        </div>
                        <div>
                            <label>End Date</label>
                            <input type="date" id="customEndDate" class="custom-date-input">
                        </div>
                        <button class="apply-custom-btn" onclick="applyCustomDate()">Apply Custom Range</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="contactsLoader" style="display: none; padding: 20px; text-align: center;">
            <div class="spinner"></div>
            <div style="margin-top: 10px; color: var(--text-secondary); font-size: 13px;">Loading chats...</div>
        </div>
        <div class="contact-list" id="contactList"></div>
    </div>

    <div class="chat-area chat-area-bg" id="chatMain">
        
        <div class="default-screen" id="defaultScreen">
            <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:20px; opacity:0.4;">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                <line x1="15" y1="9" x2="15.01" y2="9"></line>
            </svg>
            <h2 style="font-weight: 300; font-size: 28px;">GoRide Chat</h2>
            <p style="margin-top: 15px; font-size: 14px;">Select a contact from the left menu to start messaging.</p>
        </div>

        <div class="active-chat-screen" id="activeChatScreen">
            <div class="header">
                <div class="chat-header-info">
                    <button class="mobile-back" onclick="goBack()">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 20.016l-8.016-8.016 8.016-8.016 1.406 1.406-5.578 5.625h14.156v1.969h-14.156l5.578 5.625z"></path></svg>
                    </button>
                    <img src="" class="profile-img" id="headerImg">
                    <div class="chat-header-text">
                        <div class="chat-header-name-wrapper">
                            <span class="chat-header-name" id="currentChatName"></span>
                            <span class="edit-icon" onclick="openEditModal()" title="Edit Contact Name">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a.996.996 0 0 0 0-1.41l-2.34-2.34a.996.996 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"></path></svg>
                            </span>
                            <span class="edit-icon" style="margin-left: 10px;" onclick="headerMarkUnread()" title="Mark as Unread">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                            </span>
                        </div>
                        <div class="chat-header-status" id="chatStatus"></div>
                    </div>
                </div>
                <div class="search-wrapper" style="width: 200px; margin-left: 10px;">
                    <input type="text" class="search-input" id="chatSearch" placeholder="Search in chat" onkeyup="searchMessages()">
                </div>
            </div>

            <div class="messages-container" id="messageDisplay"></div>

            <div class="footer">
                <button class="icon-btn" id="footerTemplateBtn" onclick="openTemplateModal()" title="Send Template">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"></path></svg>
                </button>

                <div style="position: relative;" id="footerAttachWrapper">
                    <button class="icon-btn attach-btn-icon" id="attachBtn" onclick="toggleAttachMenu(event)" title="Attach media">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                        </svg>
                    </button>
                    <div id="attachMenu" class="attach-popup-menu" style="display:none;" onclick="event.stopPropagation()">
                        <div class="attach-menu-item" onclick="triggerMediaFile('image/*,video/*')">
                            <div class="attach-icon-circle" style="background:#ac44cf;">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="#fff"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5-7-3 3.72L9 13l-3 4h12l-4-5z"/></svg>
                            </div>
                            <span>Photos & Videos</span>
                        </div>
                        <div class="attach-menu-item" onclick="triggerMediaFile('audio/*')">
                            <div class="attach-icon-circle" style="background:#e04663;">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="#fff"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                            </div>
                            <span>Audio</span>
                        </div>
                        <div class="attach-menu-item" onclick="triggerMediaFile('*/*')">
                            <div class="attach-icon-circle" style="background:#5157ae;">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="#fff"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                            </div>
                            <span>Document</span>
                        </div>
                    </div>
                </div>

                <input type="file" id="mediaFileInput" style="display:none;" onchange="handleMediaSelected(this)">

                <textarea class="input-box" id="messageInput" placeholder="Type a message" rows="1" style="resize: none; overflow-y: auto; max-height: 120px; font-family: inherit;"></textarea>

                <div class="voice-record-bar" id="voiceRecordBar">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="voice-rec-dot"></span>
                        <span class="voice-rec-timer" id="voiceRecTimer">0:00</span>
                    </div>
                    <div class="voice-rec-waves">
                        <span></span><span></span><span></span><span></span><span></span><span></span><span></span>
                    </div>
                    <button type="button" class="voice-rec-cancel-btn" onclick="cancelVoiceRecording()" title="Cancel recording">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    </button>
                </div>

                <button class="send-btn-round" id="sendBtn" onclick="handleSendButtonClick()" title="Send" style="display:none;">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M1.101 21.757 23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"></path></svg>
                </button>

                <button class="send-btn-round" id="micBtn" onclick="startVoiceRecording()" title="Click to talk and send voice message" style="display:flex;">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<div id="msgReactionFloatingBar" class="reaction-floating-bar" style="display:none;" onclick="event.stopPropagation()">
    <span class="react-emoji" onclick="selectReaction('👍')" title="Thumbs up">👍</span>
    <span class="react-emoji" onclick="selectReaction('❤️')" title="Love">❤️</span>
    <span class="react-emoji" onclick="selectReaction('😂')" title="Laugh">😂</span>
    <span class="react-emoji" onclick="selectReaction('😮')" title="Surprised">😮</span>
    <span class="react-emoji" onclick="selectReaction('😢')" title="Sad">😢</span>
    <span class="react-emoji" onclick="selectReaction('🙏')" title="Thanks / Pray">🙏</span>
</div>

<div id="mediaSendModal" class="media-send-modal-overlay">
    <div class="media-send-header">
        <button type="button" class="media-send-close-btn" onclick="closeMediaSendModal()" title="Close">&times;</button>
        <div id="mediaSendTitle" style="font-weight: 500; font-size: 15px;">Send Media</div>
        <div style="width: 28px;"></div>
    </div>
    <div class="media-send-body" id="mediaSendPreviewContainer">
        <!-- Dynamic Preview -->
    </div>
    <div class="media-send-footer">
        <input type="text" id="mediaCaptionInput" class="media-send-caption-input" placeholder="Add a caption..." autocomplete="off">
        <button type="button" id="mediaSendConfirmBtn" class="media-send-btn" onclick="submitSendMedia()" title="Send">
            <svg id="mediaSendBtnIcon" viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M1.101 21.757 23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"/></svg>
            <div id="mediaSendSpinner" class="spinner" style="display:none; width:20px; height:20px; border-width:2px; border-left-color:#fff;"></div>
        </button>
    </div>
</div>

<div id="editNameModal" class="modal-overlay">
    <div class="modal-content">
        <h3 class="modal-title">Edit Contact Name</h3>
        <input type="text" id="editNameInput" class="modal-input" placeholder="Contact Name" autocomplete="off">
        <div class="modal-actions">
            <button class="modal-btn cancel-btn" onclick="closeEditModal()">Cancel</button>
            <button class="modal-btn save-btn" onclick="saveContactName()">Save</button>
        </div>
    </div>
</div>

<div id="templateModal" class="modal-overlay">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="modal-title" style="margin-bottom: 0;">Select Template</h3>
            <button class="icon-btn" id="syncIconBtn" onclick="syncTemplates()" title="Sync Templates" style="color: var(--text-secondary); background: var(--search-bg); padding: 6px; border-radius: 50%;">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46A7.93 7.93 0 0020 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74A7.93 7.93 0 004 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
                </svg>
            </button>
        </div>
        <div class="template-list-container" id="templateList"></div>
        <div id="syncStatus" style="font-size: 13px; font-weight: 500; text-align: center; margin-top: 10px; display: none;"></div>
        <div class="modal-actions">
            <button class="modal-btn cancel-btn" onclick="closeTemplateModal()">Cancel</button>
        </div>
    </div>
</div>

<div id="mentionModal" class="modal-overlay">
    <div class="modal-content">
        <h3 class="modal-title">Mention People</h3>
        <div class="template-list-container" id="adminList">Loading...</div>
        <div class="modal-actions">
            <button class="modal-btn cancel-btn" onclick="closeMentionModal()">Cancel</button>
        </div>
    </div>
</div>

<div id="contextMenu" class="context-menu" style="display: none;">
    <div class="context-menu-item" onclick="contextMarkUnread()">Mark as unread</div>
</div>

<div id="mediaLightboxModal" class="modal-overlay" onclick="closeMediaLightbox()">
    <div style="position: relative; max-width: 90vw; max-height: 90vh; display: flex; align-items: center; justify-content: center; flex-direction: column;" onclick="event.stopPropagation()">
        <div style="position: absolute; top: -45px; right: 0; display: flex; gap: 10px; z-index: 1001;">
            <a id="mediaLightboxDownload" href="#" download class="lightbox-btn" title="Download">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
            </a>
            <button type="button" class="lightbox-btn" onclick="closeMediaLightbox()" title="Close">&times;</button>
        </div>
        <img id="mediaLightboxImg" src="" alt="Full Preview" style="max-width: 90vw; max-height: 85vh; border-radius: 8px; object-fit: contain; box-shadow: 0 4px 24px rgba(0,0,0,0.5); display: none;" />
        <video id="mediaLightboxVideo" controls playsinline style="max-width: 90vw; max-height: 85vh; border-radius: 8px; box-shadow: 0 4px 24px rgba(0,0,0,0.5); background: #000; display: none;"></video>
    </div>
</div>

<script>
    const firebaseConfig = { 
        apiKey: "{{ env('FIREBASE_API_KEY') }}", 
        authDomain: "{{ env('FIREBASE_AUTH_DOMAIN') }}", 
        projectId: "{{ env('FIREBASE_PROJECT_ID') }}" 
    };
    
    firebase.initializeApp(firebaseConfig);
    const db = firebase.firestore();

    
    let activeChatId = null;
    let allContacts = [];
    let messageListenerUnsubscribe = null;
    let notifiedTimestamps = {}; 
    let contextMenuTargetId = null;
    let isInitialLoad = true;
    let contactsListenerUnsubscribe = null;
    
    let currentFilter = 'all'; 
    let filterStartDate = null;
    let filterEndDate = null;
    let filterMentionAdmin = null; 
    
    const myAdminUsername = "{{ session('chat_admin_username') }}"; 

    // Media Message State Management
    const mediaLoading = {};
    const mediaStored = {};
    const mediaLoaded = {};
    const mediaErrors = {};
    const chatMessagesMap = {};

    document.addEventListener('DOMContentLoaded', function() {
        if ("Notification" in window && Notification.permission !== "granted" && Notification.permission !== "denied") {
            Notification.requestPermission();
        }
        setupContactsListener();
    });

    document.addEventListener('click', function(e) {
        const dateWrapper = document.getElementById('dateFilterWrapperId');
        const dateMenu = document.getElementById('dateFilterMenu');
        if (dateWrapper && !dateWrapper.contains(e.target)) {
            dateMenu.style.display = 'none';
            document.getElementById('customDateSection').style.display = 'none';
        }
        
        const mentionWrapper = document.getElementById('mentionFilterWrapperId');
        const mentionMenu = document.getElementById('mentionFilterMenu');
        if (mentionWrapper && !mentionWrapper.contains(e.target)) {
            mentionMenu.style.display = 'none';
        }

        const contextMenu = document.getElementById('contextMenu');
        if (contextMenu && contextMenu.style.display === 'block') {
            contextMenu.style.display = 'none';
        }
    });

    async function toggleMentionMenu() {
        const menu = document.getElementById('mentionFilterMenu');
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
            if(document.getElementById('mentionFilterAdminList').innerHTML.includes('Loading...')) {
                try {
                    const res = await fetch("{{ route('chat.admins') }}");
                    const json = await res.json();
                    let html = '';
                    if (json.status && json.data) {
                        html += `<div class="preset-option" onclick="applyMentionFilter('${myAdminUsername}')">Me (${myAdminUsername})</div>`;
                        json.data.forEach(admin => {
                            if(admin.username !== myAdminUsername) {
                                html += `<div class="preset-option" onclick="applyMentionFilter('${admin.username}')">${admin.username}</div>`;
                            }
                        });
                    } else {
                        html = '<div class="preset-option" style="color:var(--text-secondary);">Error loading admins.</div>';
                    }
                    document.getElementById('mentionFilterAdminList').innerHTML = html;
                } catch (error) {
                    document.getElementById('mentionFilterAdminList').innerHTML = '<div class="preset-option" style="color:red;">Error loading admins.</div>';
                }
            }
        } else {
            menu.style.display = 'none';
        }
    }

    function applyMentionFilter(username) {
        filterMentionAdmin = username;
        document.getElementById('mentionFilterText').innerText = `${username}`;
        document.getElementById('clearMentionBtn').style.display = 'inline';
        document.getElementById('mentionFilterMenu').style.display = 'none';
        document.getElementById('mentionFilterDisplayId').classList.add('active');
        filterContacts();
    }

    function clearMentionFilter(e) {
        if(e) e.stopPropagation();
        filterMentionAdmin = null;
        document.getElementById('mentionFilterText').innerText = "@ Mentions";
        document.getElementById('clearMentionBtn').style.display = 'none';
        document.getElementById('mentionFilterMenu').style.display = 'none';
        document.getElementById('mentionFilterDisplayId').classList.remove('active');
        filterContacts();
    }

    function toggleDateMenu() {
        const menu = document.getElementById('dateFilterMenu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    function clearDateFilter(e) {
        if(e) e.stopPropagation();
        filterStartDate = null;
        filterEndDate = null;
        document.getElementById('dateFilterText').innerText = "All Dates";
        document.getElementById('clearDateBtn').style.display = 'none';
        document.getElementById('dateFilterMenu').style.display = 'none';
        document.getElementById('customDateSection').style.display = 'none';
        document.getElementById('dateFilterDisplayId').classList.remove('active');
        document.getElementById('customStartDate').value = '';
        document.getElementById('customEndDate').value = '';
        setupContactsListener();
    }

    function applyDatePreset(preset) {
        const today = new Date();
        today.setHours(0,0,0,0);
        const endOfToday = new Date(today);
        endOfToday.setHours(23,59,59,999);

        let start = new Date(today);
        let end = new Date(endOfToday);
        let label = "";

        if (preset === 'today') {
            label = "Today";
        } else if (preset === 'yesterday') {
            start.setDate(today.getDate() - 1);
            end = new Date(start);
            end.setHours(23,59,59,999);
            label = "Yesterday";
        } else if (preset === 'last7') {
            start.setDate(today.getDate() - 6);
            label = "Last 7 Days";
        } else if (preset === 'thisMonth') {
            start.setDate(1);
            label = "This Month";
        } else if (preset === 'lastMonth') {
            start.setMonth(today.getMonth() - 1);
            start.setDate(1);
            end = new Date(start);
            end.setMonth(end.getMonth() + 1);
            end.setDate(0); 
            end.setHours(23,59,59,999);
            label = "Last Month";
        } else if (preset === 'thisYear') {
            start.setMonth(0);
            start.setDate(1);
            label = "This Year";
        } else if (preset === 'lastYear') {
            start.setFullYear(today.getFullYear() - 1);
            start.setMonth(0);
            start.setDate(1);
            end = new Date(start);
            end.setFullYear(end.getFullYear() + 1);
            end.setMonth(0);
            end.setDate(0);
            end.setHours(23,59,59,999);
            label = "Last Year";
        }

        filterStartDate = start;
        filterEndDate = end;
        
        document.getElementById('dateFilterText').innerText = label;
        document.getElementById('clearDateBtn').style.display = 'inline';
        document.getElementById('dateFilterMenu').style.display = 'none';
        document.getElementById('customDateSection').style.display = 'none';
        document.getElementById('dateFilterDisplayId').classList.add('active');
        setupContactsListener();
    }

    function toggleCustomDate() {
        const sec = document.getElementById('customDateSection');
        const menu = document.getElementById('dateFilterMenu');
        if (sec.style.display === 'none') {
            sec.style.display = 'flex';
            setTimeout(() => {
                menu.scrollTo({ top: menu.scrollHeight, behavior: 'smooth' });
            }, 50);
        } else {
            sec.style.display = 'none';
        }
    }

    function applyCustomDate() {
        const startVal = document.getElementById('customStartDate').value;
        const endVal = document.getElementById('customEndDate').value;
        
        if (!startVal || !endVal) {
            alert("Please select both start and end dates.");
            return;
        }
        
        filterStartDate = new Date(startVal);
        filterStartDate.setHours(0,0,0,0);
        
        filterEndDate = new Date(endVal);
        filterEndDate.setHours(23,59,59,999);

        const formatOptions = { month: 'short', day: 'numeric' };
        const sLabel = filterStartDate.toLocaleDateString(undefined, formatOptions);
        const eLabel = filterEndDate.toLocaleDateString(undefined, formatOptions);

        document.getElementById('dateFilterText').innerText = `${sLabel} - ${eLabel}`;
        document.getElementById('clearDateBtn').style.display = 'inline';
        document.getElementById('dateFilterMenu').style.display = 'none';
        document.getElementById('customDateSection').style.display = 'none';
        document.getElementById('dateFilterDisplayId').classList.add('active');
        setupContactsListener();
    }

    function parseDate(dateInput) {
        if (!dateInput) return new Date();
        if (typeof dateInput === 'object' && typeof dateInput.toDate === 'function') return dateInput.toDate();
        let d = new Date(dateInput.toString().replace(' ', 'T'));
        if (isNaN(d)) d = new Date(dateInput);
        return d;
    }

    function formatTime(dateInput) {
        let d = parseDate(dateInput);
        if (isNaN(d)) return '';
        let h = d.getHours(), m = d.getMinutes(), a = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + (m < 10 ? '0' + m : m) + ' ' + a;
    }

    function getWhatsAppDateLabel(d) {
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);

        if (d.toDateString() === today.toDateString()) return 'TODAY';
        if (d.toDateString() === yesterday.toDateString()) return 'YESTERDAY';

        return d.toLocaleDateString('en-GB');
    }

    function formatSidebarDate(dateInput) {
        if (!dateInput) return '';
        let d = parseDate(dateInput);
        if (isNaN(d)) return '';
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);

        if (d.toDateString() === today.toDateString()) return formatTime(d);
        if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
        return d.toLocaleDateString('en-GB');
    }

    function getTickSVG(status, isFloating = false) {
        const grey = isFloating ? '#ffffff' : 'var(--grey-tick)';
        const blue = isFloating ? '#53bdeb' : 'var(--blue-tick)';
        if (status === 'sent') return `<svg viewBox="0 0 24 24"><path fill="${grey}" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`;
        if (status === 'delivered' || status === 'read') return `<svg viewBox="0 0 24 24"><path fill="${status==='read'?blue:grey}" d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12l-1.41 1.41L11.66 19l12-12-1.42-1.41zM.41 13.41L6 19l1.41-1.41L1.83 12 .41 13.41z"/></svg>`;
        return `<svg viewBox="0 0 24 24" style="width:15px;height:15px;"><path fill="${grey}" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>`;
    }

    function cleanText(t) { return t ? t.replace(/\[|\]/g, '').replace(/ template$/i, '').trim() : 'Media'; }

    function setFilter(type) {
        currentFilter = type; 
        document.getElementById('btnFilterAll').classList.remove('active');
        document.getElementById('btnFilterUnread').classList.remove('active-green');
        
        if(type === 'all') document.getElementById('btnFilterAll').classList.add('active');
        if(type === 'unread') document.getElementById('btnFilterUnread').classList.add('active-green');
        
        filterContacts();
    }

    function filterContacts() {
        const query = document.getElementById('sidebarSearch').value.toLowerCase();
        renderContacts(query);
    }

    function searchMessages() {
        const query = document.getElementById('chatSearch').value.toLowerCase();
        const messages = document.querySelectorAll('.msg span');
        let firstMatch = null;

        messages.forEach(msg => {
            const text = msg.innerText.toLowerCase();
            if (query && text.includes(query)) {
                msg.classList.add('highlight');
                if (!firstMatch) firstMatch = msg;
            } else {
                msg.classList.remove('highlight');
            }
        });

        if (firstMatch) firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function toDbDateString(date) {
        const pad = (n) => n.toString().padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
    }

    function setupContactsListener() {
        if (contactsListenerUnsubscribe) {
            contactsListenerUnsubscribe();
        }
        
        document.getElementById('contactList').innerHTML = ''; 
        document.getElementById('contactsLoader').style.display = 'block';

        isInitialLoad = true;
        let query = db.collection('contacts');

        if (filterStartDate && filterEndDate) {
            query = query.where('last_message_at', '>=', toDbDateString(filterStartDate))
                         .where('last_message_at', '<=', toDbDateString(filterEndDate));
        }

        query = query.orderBy('last_message_at', 'desc').limit(100);

        contactsListenerUnsubscribe = query.onSnapshot(snap => {
            document.getElementById('contactsLoader').style.display = 'none';
            
            allContacts = [];
            
            snap.docChanges().forEach(change => {
                const data = change.doc.data();
                const unreadCount = data.unread_count || 0;

                if (change.type === 'added' && isInitialLoad) {
                    notifiedTimestamps[change.doc.id] = data.last_message_at;
                }
                
                if (change.type === 'modified' || change.type === 'added') {
                    if (change.doc.id === activeChatId) {
                        if (unreadCount > 0) {
                            db.collection('contacts').doc(activeChatId).update({ unread_count: 0 }).catch(()=>{});
                        }
                    } else {
                        if (unreadCount > 0 && notifiedTimestamps[change.doc.id] !== data.last_message_at && !isInitialLoad) {
                            notifiedTimestamps[change.doc.id] = data.last_message_at;
                            
                            if ("Notification" in window && Notification.permission === "granted") {
                                const title = (data.name && data.name !== 'null') ? data.name : change.doc.id;
                                new Notification(title, {
                                    body: cleanText(data.last_message),
                                    icon: "https://www.goride.run/goride/img/logo-light.png"
                                });
                            }
                        }
                    }
                }
            });

            isInitialLoad = false;

            snap.forEach(doc => {
                const d = doc.data();
                allContacts.push({ 
                    id: doc.id, 
                    name: (d.name && d.name!=='null') ? d.name : doc.id, 
                    lastMsg: cleanText(d.last_message), 
                    time: d.last_message_at,
                    unreadCount: (doc.id === activeChatId) ? 0 : (d.unread_count || 0),
                    mentioned: d.mentioned || null
                });
            });
            
            filterContacts(); 
        });
    }

    function renderContacts(query = '') {
        let html = '';
        let matchCount = 0; 

        allContacts.forEach(c => {
            const matchesSearch = c.name.toLowerCase().includes(query) || c.id.includes(query);
            const matchesUnread = currentFilter === 'unread' ? c.unreadCount > 0 : true;
            
            const matchesMention = filterMentionAdmin ? c.mentioned === filterMentionAdmin : true;
            
            let matchesDate = true;
            if (filterStartDate && filterEndDate) {
                const contactDateObj = parseDate(c.time);
                if (!isNaN(contactDateObj)) {
                    matchesDate = contactDateObj >= filterStartDate && contactDateObj <= filterEndDate;
                } else {
                    matchesDate = false;
                }
            }

            if (matchesSearch && matchesUnread && matchesMention && matchesDate) {
                matchCount++; 
                const hasUnread = c.unreadCount > 0;
                const nameStyle = hasUnread ? 'font-weight:600; color:var(--text-primary);' : 'font-weight:400; color:var(--text-primary);';
                const timeStyle = hasUnread ? 'font-weight:600; color:var(--accent-green);' : 'font-weight:400; color:var(--text-secondary);';
                const msgStyle  = hasUnread ? 'font-weight:600; color:var(--text-primary);' : 'font-weight:400; color:var(--text-secondary);';
                
                let mentionBadge = '';
                if(c.mentioned) {
                    mentionBadge = `<span class="mention-badge">${c.mentioned}</span>`;
                }
                
                html += `<div class="contact ${activeChatId===c.id?'active':''}" 
                              onclick="openChat('${c.id}', '${c.name.replace(/'/g, "\\'")}')"
                              oncontextmenu="showContextMenu(event, '${c.id}')">
                    <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(c.name)}&background=random" class="profile-img">
                    <div class="contact-details">
                        <div class="contact-top">
                            <span class="contact-name" style="${nameStyle}">${mentionBadge} ${c.name}</span>
                            <span class="contact-time" style="${timeStyle}">${formatSidebarDate(c.time)}</span>
                        </div>
                        <div class="contact-bottom">
                            <div class="contact-last-msg" style="${msgStyle}">${c.lastMsg}</div>
                            ${hasUnread ? `<div class="unread-badge">${c.unreadCount}</div>` : ''}
                        </div>
                    </div>
                    <div class="contact-more" onclick="openMentionModal(event, '${c.id}')" title="Mention Admin">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 7a2 2 0 1 0-.001-4.001A2 2 0 0 0 12 7zm0 2a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 9zm0 6a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 15z"></path></svg>
                    </div>
                </div>`;
            }
        });

        if (matchCount === 0) {
            let emptyMessage = "No chats found";
            if (currentFilter === 'unread') emptyMessage = "No unread messages";
            if (filterMentionAdmin) emptyMessage = `No mentions found for ${filterMentionAdmin}`;
            if (filterStartDate && filterEndDate) emptyMessage = "No chats found in selected date range";
            if (query !== '') emptyMessage = `No results for "${query}"`;

            html = `
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding: 60px 20px; color: var(--text-secondary); text-align: center;">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 16px; opacity: 0.4;">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span style="font-size: 15px; font-weight: 500;">${emptyMessage}</span>
                </div>
            `;
        }

        document.getElementById('contactList').innerHTML = html;
    }

    let mentionTargetId = null;
    
    async function openMentionModal(e, contactId) {
        e.stopPropagation();
        mentionTargetId = contactId;
        document.getElementById('mentionModal').style.display = 'flex';
        document.getElementById('adminList').innerHTML = 'Loading...';
        
        try {
            const res = await fetch("{{ route('chat.admins') }}");
            const json = await res.json();
            let html = '';
            
            if (json.status && json.data) {
                json.data.forEach(admin => {
                    html += `<button class="template-item" onclick="mentionAdmin('${admin.username}')">${admin.username}</button>`;
                });
                html += `<button class="template-item" style="color:var(--text-secondary); text-align:center; margin-top:10px; border:1px solid #ddd;" onclick="mentionAdmin(null)">Remove Mention</button>`;
            } else {
                html = '<p>Error loading admins.</p>';
            }
            document.getElementById('adminList').innerHTML = html;
        } catch (error) {
            document.getElementById('adminList').innerHTML = '<p>Error loading admins.</p>';
        }
    }

    async function mentionAdmin(username) {
        if (!mentionTargetId) return;
        
        await db.collection('contacts').doc(mentionTargetId).update({ 
            mentioned: username 
        });
        
        closeMentionModal();
    }

    function closeMentionModal() {
        document.getElementById('mentionModal').style.display = 'none';
        mentionTargetId = null;
    }

    function openChat(id, name) {
        activeChatId = id;
        
        document.getElementById('defaultScreen').style.display = 'none';
        document.getElementById('activeChatScreen').style.display = 'flex';
        document.getElementById('appContainer').classList.add('show-chat');

        document.getElementById('currentChatName').innerText = name;
        document.getElementById('chatStatus').innerText = (name!==id) ? id : 'online';
        document.getElementById('headerImg').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random`;
        document.getElementById('chatSearch').value = '';

        const input = document.getElementById('messageInput');
        input.value = '';
        input.style.height = 'auto';
        input.focus();
        resetVoiceRecordingUI();

        db.collection('contacts').doc(id).update({ unread_count: 0 }).catch(()=>{});

        filterContacts(); 
        if(messageListenerUnsubscribe) messageListenerUnsubscribe();

        messageListenerUnsubscribe = db.collection('contacts').doc(id).collection('messages')
            .orderBy('timestamp', 'desc')
            .limit(50)
            .onSnapshot(snap => {
                let messagesData = [];
                
                snap.forEach(doc => {
                    const d = doc.data() || {};
                    if (!d.wa_message_id) d.wa_message_id = doc.id;
                    messagesData.push(d);
                });

                messagesData.sort((a, b) => {
                    const dateA = parseDate(a.timestamp);
                    const dateB = parseDate(b.timestamp);
                    return dateA - dateB;
                });

                let html = '';
                let lastDateStr = null;

                messagesData.forEach(m => {
                    if (m.wa_message_id) {
                        chatMessagesMap[m.wa_message_id] = m;
                    }
                    const msgDateObj = parseDate(m.timestamp);
                    const currentDateStr = msgDateObj.toDateString();

                    if (currentDateStr !== lastDateStr) {
                        html += `<div class="date-divider"><span>${getWhatsAppDateLabel(msgDateObj)}</span></div>`;
                        lastDateStr = currentDateStr;
                    }

                    const isOut = m.direction === 'out';
                    const waId = m.wa_message_id || '';
                    const hasCaption = Boolean(m.caption && String(m.caption).trim());
                    let type = (m.type || '').toLowerCase();
                    const filename = (m.filename || '').toLowerCase();
                    const mimeType = (m.mime_type || '').toLowerCase();
                    if (filename.startsWith('voice_note_') || mimeType.includes('audio') || (type === 'video' && (m.text === '[audio message]' || m.caption === '[audio message]'))) {
                        type = 'audio';
                    }
                    // Direct URL check: S3 URL is present, or blob URL loaded in memory, or outgoing media preview
                    const hasDirectMediaUrl = Boolean(m.s3_url) || Boolean(waId && mediaLoaded[waId]?.url) || (isOut && Boolean(m.media_view_url || m.url));
                    const isVisualMedia = ['image', 'video'].includes(type) && hasDirectMediaUrl;

                    const reactPillHtml = m.reaction ? `<div class="msg-reaction-pill" onclick="openReactionPicker(event, '${escapeHtml(waId)}')" title="Reaction">${escapeHtml(m.reaction)}</div>` : '';
                    const reactBtnHtml = waId ? `<button type="button" class="msg-react-trigger" onclick="openReactionPicker(event, '${escapeHtml(waId)}')" title="React"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg></button>` : '';

                    if (isMediaMessage(m)) {
                        const mediaBubbleClass = isVisualMedia ? (hasCaption ? 'msg-media-bubble msg-media-captioned' : 'msg-media-bubble') : '';
                        html += `<div class="msg ${isOut?'msg-out':'msg-in'} ${mediaBubbleClass}" data-wa-msg-id="${escapeHtml(waId)}">
                            ${reactBtnHtml}
                            ${renderMediaMessageContent(m)}
                            ${!isVisualMedia ? `
                            <div class="msg-meta"><span class="msg-time">${formatTime(msgDateObj)}</span>${isOut?`<span class="msg-status">${getTickSVG(m.status)}</span>`:''}</div>
                            ` : ''}
                            ${reactPillHtml}
                        </div>`;
                    } else {
                        html += `<div class="msg ${isOut?'msg-out':'msg-in'}" data-wa-msg-id="${escapeHtml(waId)}">
                            ${reactBtnHtml}
                            <span>${cleanText(m.text || m.button_text || m.template)}</span>
                            <div class="msg-meta"><span class="msg-time">${formatTime(msgDateObj)}</span>${isOut?`<span class="msg-status">${getTickSVG(m.status)}</span>`:''}</div>
                            ${reactPillHtml}
                        </div>`;
                    }
                });
                
                const box = document.getElementById('messageDisplay');
                box.innerHTML = html; 
                box.scrollTop = box.scrollHeight;
            });
    }

    // ==========================================
    // WHATSAPP MEDIA RENDERING & INTEGRATION
    // ==========================================

    function escapeHtml(text) {
        if (!text && text !== 0) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function isMediaMessage(m) {
        if (!m) return false;
        const type = (m.type || '').toLowerCase();
        return ['image', 'video', 'audio', 'document', 'sticker'].includes(type);
    }

    // Guard against Firestore Timestamp objects or message IDs being used as WA media IDs
    function isValidMediaId(id) {
        if (!id) return false;
        const str = String(id).trim();
        if (str.startsWith('Timestamp') || str.includes('seconds=') || str.length > 80) return false;
        if (str.startsWith('wamid.') || str.startsWith('out_')) return false;
        return true;
    }

    function getCandidateMediaId(m) {
        if (!m) return null;
        const type = (m.type || '').toLowerCase();
        const candidate = m.media_id 
            || m.mediaId 
            || (m[type] && m[type].id)
            || (m.image && m.image.id)
            || (m.video && m.video.id)
            || (m.audio && m.audio.id)
            || (m.document && m.document.id)
            || (m.sticker && m.sticker.id)
            || (m.id && !String(m.id).startsWith('wamid.') && !String(m.id).startsWith('out_') ? m.id : null);
        if (candidate && isValidMediaId(candidate)) return String(candidate).trim();
        return null;
    }

    function getMediaViewUrl(m) {
        // Always build from current origin to avoid stale/wrong-domain URLs stored in Firebase
        if (m.wa_message_id) return `${window.location.origin}/api/whatsapp/media/${encodeURIComponent(m.wa_message_id)}/view`;
        return null;
    }

    function getMediaStoreUrl(m) {
        // Always build from current origin to avoid stale/wrong-domain URLs stored in Firebase
        if (m.wa_message_id) return `${window.location.origin}/api/whatsapp/media/${encodeURIComponent(m.wa_message_id)}/store`;
        return null;
    }

    function getViewButtonLabel(type) {
        switch ((type || '').toLowerCase()) {
            case 'image': return 'View Image';
            case 'video': return 'View Video';
            case 'audio': return 'Play Audio';
            case 'document': return 'View';
            case 'sticker': return 'View Sticker';
            default: return 'View Media';
        }
    }

    function renderMediaMessageContent(m) {
        let type = (m.type || '').toLowerCase();
        const filename = (m.filename || '').toLowerCase();
        const mimeType = (m.mime_type || '').toLowerCase();
        if (filename.startsWith('voice_note_') || mimeType.includes('audio') || (type === 'video' && (m.text === '[audio message]' || m.caption === '[audio message]'))) {
            type = 'audio';
        }
        const waId = m.wa_message_id || '';
        // isStored: media_status==='stored' in Firebase OR storeMedia() was just called successfully
        const isStored = (m.media_status === 'stored') || Boolean(waId && mediaStored[waId]);
        const isLoadingView = Boolean(waId && mediaLoading[waId] === 'viewing');
        const isLoadingStore = Boolean(waId && mediaLoading[waId] === 'storing');
        // loaded: set only after user clicks View and fetch/blob succeeds
        const loaded = waId ? mediaLoaded[waId] : null;
        const errorMsg = waId ? mediaErrors[waId] : null;
        const hasCaption = Boolean(m.caption && String(m.caption).trim());

        // The /view endpoint serves from S3 if stored, or fetches from Meta — always correct domain
        const viewUrl = getMediaViewUrl(m);

        let contentHtml = '';

        // Direct URL is available when:
        // 1. m.s3_url is set (stored in AWS S3)
        // 2. mediaLoaded[waId]?.url is set (user clicked View or just stored)
        // 3. Outgoing message with local preview URL (m.media_view_url or m.url)
        const isOut = m.direction === 'out';
        const directMediaUrl = m.s3_url || (mediaLoaded[waId] && mediaLoaded[waId].url) || (isOut && (m.media_view_url || m.url)) || null;

        // === CASE 1: Direct URL is ready — render inline image / video / audio / sticker ===
        if (directMediaUrl && ['image', 'video', 'audio', 'sticker'].includes(type)) {
            const srcUrl = directMediaUrl;
            if (type === 'image') {
                contentHtml = `
                    <div class="media-rendered-content">
                        <img src="${escapeHtml(srcUrl)}" alt="WhatsApp image" class="chat-media-img"
                            onclick="openMediaLightbox('${escapeHtml(srcUrl)}')" title="Click to enlarge"
                            onload="const b = document.getElementById('messageDisplay'); if(b) b.scrollTop = b.scrollHeight;"
                            onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\\'media-placeholder-card\\'><div class=\\'media-placeholder-info\\'><span class=\\'media-placeholder-icon\\'>🖼️</span><span class=\\'media-placeholder-title\\'>Image</span></div><div class=\\'media-actions\\'><button type=\\'button\\' class=\\'media-btn media-btn-primary\\' onclick=\\'viewMedia(\\' + JSON.stringify(\\'${escapeHtml(waId)}\\') + \\')\\'>View Image</button></div></div>';" />
                        ${!hasCaption ? `
                        <div class="msg-meta msg-meta-floating">
                            <span class="msg-time">${formatTime(parseDate(m.timestamp))}</span>
                            ${isOut ? `<span class="msg-status">${getTickSVG(m.status, true)}</span>` : ''}
                        </div>` : `
                        <div class="media-caption-box">
                            <span class="media-caption-text">${escapeHtml(m.caption)}</span>
                            <div class="msg-meta"><span class="msg-time">${formatTime(parseDate(m.timestamp))}</span>${isOut ? `<span class="msg-status">${getTickSVG(m.status)}</span>` : ''}</div>
                        </div>`}
                    </div>`;
            } else if (type === 'video') {
                contentHtml = `
                    <div class="media-rendered-content">
                        <div class="media-video-container">
                            <video controls preload="metadata" playsinline class="chat-media-video" src="${escapeHtml(srcUrl)}" onloadedmetadata="const b = document.getElementById('messageDisplay'); if(b) b.scrollTop = b.scrollHeight;">
                                <source src="${escapeHtml(srcUrl)}" ${m.mime_type ? `type="${escapeHtml(m.mime_type)}"` : ''}>
                                Your browser does not support HTML video.
                            </video>
                            <button type="button" class="video-expand-btn" onclick="openMediaLightbox('${escapeHtml(srcUrl)}', 'video')" title="Watch full screen">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
                            </button>
                            ${!hasCaption ? `
                            <div class="msg-meta msg-meta-floating" style="bottom: 10px; right: 10px;">
                                <span class="msg-time">${formatTime(parseDate(m.timestamp))}</span>
                                ${isOut ? `<span class="msg-status">${getTickSVG(m.status, true)}</span>` : ''}
                            </div>` : ''}
                        </div>
                        ${hasCaption ? `
                        <div class="media-caption-box">
                            <span class="media-caption-text">${escapeHtml(m.caption)}</span>
                            <div class="msg-meta"><span class="msg-time">${formatTime(parseDate(m.timestamp))}</span>${isOut ? `<span class="msg-status">${getTickSVG(m.status)}</span>` : ''}</div>
                        </div>` : ''}
                    </div>`;
            } else if (type === 'audio') {
                contentHtml = `
                    <div class="media-rendered-content">
                        <div class="chat-vn-player" data-wa-id="${escapeHtml(waId)}">
                            <button type="button" class="vn-play-circle" onclick="toggleAudioPlay(this, '${escapeHtml(srcUrl)}')">
                                <svg class="vn-icon-play" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                <svg class="vn-icon-pause" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="display:none;"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                            </button>
                            <div class="vn-track-wrapper" onclick="toggleAudioPlay(this.previousElementSibling, '${escapeHtml(srcUrl)}')">
                                <div class="vn-waveform">
                                    <span class="vn-bar" style="height:35%"></span>
                                    <span class="vn-bar" style="height:65%"></span>
                                    <span class="vn-bar" style="height:100%"></span>
                                    <span class="vn-bar" style="height:60%"></span>
                                    <span class="vn-bar" style="height:85%"></span>
                                    <span class="vn-bar" style="height:50%"></span>
                                    <span class="vn-bar" style="height:90%"></span>
                                    <span class="vn-bar" style="height:40%"></span>
                                    <span class="vn-bar" style="height:75%"></span>
                                    <span class="vn-bar" style="height:55%"></span>
                                    <span class="vn-bar" style="height:95%"></span>
                                    <span class="vn-bar" style="height:45%"></span>
                                    <span class="vn-bar" style="height:80%"></span>
                                    <span class="vn-bar" style="height:65%"></span>
                                </div>
                                <div class="vn-timeline"><span class="vn-current-time">0:00</span><span>Voice message</span></div>
                            </div>
                            <audio preload="metadata" src="${escapeHtml(srcUrl)}" style="display:none;" ontimeupdate="onVnTimeUpdate(this)" onended="onVnEnded(this)" onloadedmetadata="onVnLoadedMeta(this)"></audio>
                        </div>
                    </div>`;
            } else if (type === 'sticker') {
                contentHtml = `
                    <div class="media-rendered-content">
                        <img src="${escapeHtml(srcUrl)}" alt="WhatsApp sticker" class="chat-media-sticker" />
                    </div>`;
            }

            if (!isOut && isStored && (hasCaption || !['image', 'video'].includes(type))) {
                contentHtml += `<div class="media-store-bar"><span class="media-stored-badge">✓ Stored</span></div>`;
            } else if (!isOut && !isStored && (hasCaption || !['image', 'video'].includes(type))) {
                contentHtml += `<div class="media-store-bar">
                    <button type="button" class="media-btn media-btn-store" onclick="storeMedia('${escapeHtml(waId)}')" ${isLoadingStore ? 'disabled' : ''}>
                        ${isLoadingStore ? 'Storing...' : 'Store in S3'}
                    </button>
                </div>`;
            }

        // === CASE 2: Document loaded ===
        } else if (loaded && loaded.url && type === 'document') {
            contentHtml = `
                <div class="media-placeholder-card">
                    <div class="media-placeholder-info">
                        <span class="media-placeholder-icon">📄</span>
                        <span class="media-doc-name" title="${escapeHtml(m.filename || 'Document')}">${escapeHtml(m.filename || 'Document')}</span>
                    </div>
                    <div class="media-actions">
                        <button type="button" class="media-btn media-btn-primary" onclick="viewMedia('${escapeHtml(waId)}')">
                            Open Document
                        </button>
                    </div>
                </div>`;
            if (!isOut) {
                contentHtml += `<div class="media-store-bar">
                    ${isStored ? `<span class="media-stored-badge">✓ Stored</span>` : `
                    <button type="button" class="media-btn media-btn-store" onclick="storeMedia('${escapeHtml(waId)}')" ${isLoadingStore ? 'disabled' : ''}>
                        ${isLoadingStore ? 'Storing...' : 'Store in S3'}
                    </button>`}
                </div>`;
            }

        // === CASE 3: Not yet loaded / No direct URL — show placeholder card with View / Store buttons ===
        } else {
            let icon = '🖼️', title = 'Image';
            if (type === 'video')    { icon = '🎥'; title = 'Video'; }
            else if (type === 'audio')    { icon = '🎵'; title = 'Audio'; }
            else if (type === 'document') { icon = '📄'; title = m.filename ? escapeHtml(m.filename) : 'Document'; }
            else if (type === 'sticker')  { icon = '🎨'; title = 'Sticker'; }

            const viewBtnLabel = getViewButtonLabel(type);

            contentHtml = `
                <div class="media-placeholder-card">
                    <div class="media-placeholder-info">
                        <span class="media-placeholder-icon">${icon}</span>
                        <span class="${type === 'document' && m.filename ? 'media-doc-name' : 'media-placeholder-title'}">${title}</span>
                    </div>
                    <div class="media-actions">
                        <button type="button" class="media-btn media-btn-primary"
                            onclick="viewMedia('${escapeHtml(waId)}')"
                            ${isLoadingView || isLoadingStore || !waId ? 'disabled' : ''}>
                            ${isLoadingView ? 'Loading...' : viewBtnLabel}
                        </button>
                        ${waId && !isStored ? `
                            <button type="button" class="media-btn media-btn-store"
                                onclick="storeMedia('${escapeHtml(waId)}')"
                                ${isLoadingStore || isLoadingView ? 'disabled' : ''}>
                                ${isLoadingStore ? 'Storing...' : 'Store'}
                            </button>` : (isStored ? `<span class="media-stored-badge">✓ Stored</span>` : '')
                        }
                    </div>
                </div>`;
        }

        if (errorMsg) {
            contentHtml += `<div class="media-error-text">${escapeHtml(errorMsg)}</div>`;
        }

        if (hasCaption && !(['image', 'video'].includes(type) && directMediaUrl)) {
            contentHtml += `<div class="media-caption"><span>${escapeHtml(m.caption)}</span></div>`;
        }

        return `<div class="media-container" id="media-content-${escapeHtml(waId)}">${contentHtml}</div>`;
    }

    function updateMessageMediaUI(waMessageId) {
        if (!waMessageId) return;
        const el = document.getElementById('media-content-' + waMessageId);
        const m = chatMessagesMap[waMessageId];
        if (el && m) {
            el.outerHTML = renderMediaMessageContent(m);
            const msgEl = document.querySelector(`.msg[data-wa-msg-id="${waMessageId}"]`);
            const hasCaption = Boolean(m.caption && String(m.caption).trim());
            const type = (m.type || '').toLowerCase();
            const hasDirectUrl = Boolean(m.s3_url) || Boolean(mediaLoaded[waMessageId]?.url) || (m.direction === 'out' && Boolean(m.media_view_url || m.url));
            if (msgEl && ['image', 'video'].includes(type) && hasDirectUrl) {
                msgEl.classList.add('msg-media-bubble');
                if (hasCaption) {
                    msgEl.classList.add('msg-media-captioned');
                } else {
                    msgEl.classList.remove('msg-media-captioned');
                }
                const bottomMeta = msgEl.querySelector(':scope > .msg-meta:not(.msg-meta-floating)');
                if (bottomMeta) bottomMeta.remove();
            }
        }
    }

    async function viewMedia(waMessageId) {
        if (!waMessageId) return;
        const m = chatMessagesMap[waMessageId];
        if (!m) return;

        if (mediaLoaded[waMessageId] && mediaLoaded[waMessageId].url) {
            if ((m.type || '').toLowerCase() === 'document') {
                window.open(mediaLoaded[waMessageId].url, '_blank');
            }
            return;
        }

        const type = (m.type || 'image').toLowerCase();

        // Build view URL — append Firebase fields so backend doesn't need a DB lookup
        let viewUrl = getMediaViewUrl(m);
        if (!viewUrl) {
            mediaErrors[waMessageId] = 'Unable to load media. View URL unavailable.';
            updateMessageMediaUI(waMessageId);
            return;
        }
        const viewParams = new URLSearchParams();
        const candidateMediaId = getCandidateMediaId(m);
        if (candidateMediaId) viewParams.set('media_id', candidateMediaId);
        if (m.mime_type)  viewParams.set('mime_type', m.mime_type);
        if (m.type)       viewParams.set('type',      m.type);
        if (activeChatId) viewParams.set('wa_id',     activeChatId);
        const paramStr = viewParams.toString();
        if (paramStr) viewUrl += (viewUrl.includes('?') ? '&' : '?') + paramStr;

        mediaLoading[waMessageId] = 'viewing';
        delete mediaErrors[waMessageId];
        updateMessageMediaUI(waMessageId);

        let docTab = null;
        if (type === 'document') {
            docTab = window.open('about:blank', '_blank');
        }

        try {
            const res = await fetch(viewUrl, { credentials: 'same-origin' });
            
            if (res.status === 401) {
                if (docTab) docTab.close();
                window.location.href = '/chat-login';
                return;
            }

            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }

            const contentType = res.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const data = await res.json().catch(() => ({}));
                if (data.status === false) {
                    throw new Error(data.message || data.error || 'Server error loading media');
                }
            }

            const blob = await res.blob();
            if (blob.size === 0) {
                throw new Error('Empty response received from media server');
            }

            const blobUrl = URL.createObjectURL(blob);
            mediaLoaded[waMessageId] = { url: blobUrl, type: type, filename: m.filename };
            mediaLoading[waMessageId] = false;
            delete mediaErrors[waMessageId];

            if (type === 'document') {
                if (docTab) {
                    docTab.location.href = blobUrl;
                } else {
                    window.open(blobUrl, '_blank');
                }
            }

            updateMessageMediaUI(waMessageId);

            // Auto-play audio/video after loading when user clicked the play button
            if (type === 'audio' || type === 'video') {
                const container = document.getElementById('media-content-' + waMessageId);
                if (container) {
                    if (type === 'audio') {
                        const playBtn = container.querySelector('.vn-play-circle');
                        if (playBtn) playBtn.click();
                    } else if (type === 'video') {
                        const mediaEl = container.querySelector('video');
                        if (mediaEl) {
                            mediaEl.load();
                            mediaEl.play().catch(() => {});
                        }
                    }
                }
            }

        } catch (err) {
            console.warn('Media fetch error:', err);
            
            if (type === 'image' || type === 'sticker') {
                const testImg = new Image();
                testImg.onload = function() {
                    mediaLoaded[waMessageId] = { url: viewUrl, type: type };
                    mediaLoading[waMessageId] = false;
                    delete mediaErrors[waMessageId];
                    updateMessageMediaUI(waMessageId);
                };
                testImg.onerror = function() {
                    mediaLoading[waMessageId] = false;
                    mediaErrors[waMessageId] = 'Unable to load media. Please try again.';
                    updateMessageMediaUI(waMessageId);
                };
                testImg.src = viewUrl;
            } else {
                if (docTab) docTab.close();
                mediaLoading[waMessageId] = false;
                mediaErrors[waMessageId] = 'Unable to load media. Please try again.';
                updateMessageMediaUI(waMessageId);
            }
        }
    }

    async function storeMedia(waMessageId) {
        if (!waMessageId) return;
        const m = chatMessagesMap[waMessageId];
        if (!m) return;

        const storeUrl = getMediaStoreUrl(m);
        if (!storeUrl) {
            mediaErrors[waMessageId] = 'Unable to store media. Store URL unavailable.';
            updateMessageMediaUI(waMessageId);
            return;
        }

        mediaLoading[waMessageId] = 'storing';
        delete mediaErrors[waMessageId];
        updateMessageMediaUI(waMessageId);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            // Send Firebase fields in body so backend uses media_id directly (no DB lookup needed)
            const candidateMediaId = getCandidateMediaId(m);
            const postBody = {
                media_id:  candidateMediaId || null,
                mime_type: m.mime_type || null,
                type:      m.type      || null,
                wa_id:     activeChatId || null,  // contact phone number
            };
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(postBody),
                credentials: 'same-origin'
            });

            if (res.status === 401) {
                window.location.href = '/chat-login';
                return;
            }

            const data = await res.json().catch(() => null);

            if (!res.ok) {
                console.error('Store Media Failed:', data);
                let errMsg = 'Unable to store media. Please try again.';
                if (data && data.error && typeof data.error === 'string' && !data.error.includes('could not be found')) {
                    errMsg = data.error;
                }
                mediaErrors[waMessageId] = errMsg;
                mediaLoading[waMessageId] = false;
                updateMessageMediaUI(waMessageId);
                return;
            }

            if (data && (data.status === true || data.stored === true || data.already_stored === true)) {
                mediaStored[waMessageId] = true;
                mediaLoading[waMessageId] = false;
                delete mediaErrors[waMessageId];

                // Use s3_url from response to display immediately (public S3 URL, no auth needed)
                const displayUrl = data.s3_url || data.view_url || null;
                if (displayUrl) {
                    const type = (m.type || 'image').toLowerCase();
                    mediaLoaded[waMessageId] = { url: displayUrl, type, filename: m.filename };
                    // Also save s3_url into Firebase so it persists across reloads
                    if (data.s3_url && activeChatId) {
                        try {
                            const safeDocId = encodeURIComponent(waMessageId);
                            db.collection('contacts').doc(activeChatId)
                              .collection('messages').doc(safeDocId)
                              .update({ s3_url: data.s3_url, media_status: 'stored' })
                              .catch(e => console.warn('Firebase s3_url update failed:', e));
                            // Also update local chatMessagesMap so re-render uses s3_url
                            m.s3_url = data.s3_url;
                            m.media_status = 'stored';
                        } catch(e) { /* ignore */ }
                    }
                }

                if (data.view_url) {
                    m.media_view_url = data.view_url;
                }
                updateMessageMediaUI(waMessageId);
            } else {
                mediaErrors[waMessageId] = (data && (data.message || data.error)) || 'Unable to store media. Please try again.';
                mediaLoading[waMessageId] = false;
                updateMessageMediaUI(waMessageId);
            }
        } catch (err) {
            console.error('Store Media Error:', err);
            mediaErrors[waMessageId] = 'Unable to store media. Please try again.';
            mediaLoading[waMessageId] = false;
            updateMessageMediaUI(waMessageId);
        }
    }

    function openMediaLightbox(url, type = 'image') {
        const modal = document.getElementById('mediaLightboxModal');
        const img = document.getElementById('mediaLightboxImg');
        const video = document.getElementById('mediaLightboxVideo');
        const dl = document.getElementById('mediaLightboxDownload');
        if (!modal || !url) return;

        if (type === 'video') {
            if (img) { img.style.display = 'none'; img.src = ''; }
            if (video) {
                video.style.display = 'block';
                video.src = url;
                video.load();
                video.play().catch(() => {});
            }
        } else {
            if (video) {
                video.pause();
                video.src = '';
                video.style.display = 'none';
            }
            if (img) {
                img.style.display = 'block';
                img.src = url;
            }
        }
        if (dl) dl.href = url;
        modal.style.display = 'flex';
    }

    function closeMediaLightbox() {
        const modal = document.getElementById('mediaLightboxModal');
        const img = document.getElementById('mediaLightboxImg');
        const video = document.getElementById('mediaLightboxVideo');
        if (video) {
            video.pause();
            video.src = '';
            video.style.display = 'none';
        }
        if (img) {
            img.src = '';
            img.style.display = 'none';
        }
        if (modal) modal.style.display = 'none';
    }

    // Audio Voice Note Player Controls
    let currentPlayingAudio = null;

    function toggleAudioPlay(btn, url) {
        const container = btn.closest('.chat-vn-player');
        if (!container) return;
        const audio = container.querySelector('audio');
        if (!audio) return;

        if (audio.paused) {
            if (currentPlayingAudio && currentPlayingAudio !== audio) {
                currentPlayingAudio.pause();
                const prevBtn = currentPlayingAudio.closest('.chat-vn-player')?.querySelector('.vn-play-circle');
                if (prevBtn) {
                    const p1 = prevBtn.querySelector('.vn-icon-play');
                    const p2 = prevBtn.querySelector('.vn-icon-pause');
                    if (p1) p1.style.display = 'block';
                    if (p2) p2.style.display = 'none';
                }
            }
            audio.play().then(() => {
                currentPlayingAudio = audio;
                const p1 = btn.querySelector('.vn-icon-play');
                const p2 = btn.querySelector('.vn-icon-pause');
                if (p1) p1.style.display = 'none';
                if (p2) p2.style.display = 'block';
            }).catch(e => console.warn('Audio play error:', e));
        } else {
            audio.pause();
            const p1 = btn.querySelector('.vn-icon-play');
            const p2 = btn.querySelector('.vn-icon-pause');
            if (p1) p1.style.display = 'block';
            if (p2) p2.style.display = 'none';
        }
    }

    function onVnTimeUpdate(audio) {
        const container = audio.closest('.chat-vn-player');
        if (!container) return;
        const timeEl = container.querySelector('.vn-current-time');
        if (timeEl && audio.duration) {
            const cur = Math.floor(audio.currentTime);
            const mins = Math.floor(cur / 60);
            const secs = cur % 60;
            timeEl.innerText = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
        }
        const bars = container.querySelectorAll('.vn-bar');
        if (bars.length && audio.duration) {
            const progressRatio = audio.currentTime / audio.duration;
            const activeBars = Math.floor(progressRatio * bars.length);
            bars.forEach((bar, idx) => {
                if (idx <= activeBars) bar.classList.add('played');
                else bar.classList.remove('played');
            });
        }
    }

    function onVnEnded(audio) {
        const container = audio.closest('.chat-vn-player');
        if (!container) return;
        const btn = container.querySelector('.vn-play-circle');
        if (btn) {
            const p1 = btn.querySelector('.vn-icon-play');
            const p2 = btn.querySelector('.vn-icon-pause');
            if (p1) p1.style.display = 'block';
            if (p2) p2.style.display = 'none';
        }
        const bars = container.querySelectorAll('.vn-bar');
        bars.forEach(b => b.classList.remove('played'));
        const timeEl = container.querySelector('.vn-current-time');
        if (timeEl && audio.duration) {
            const cur = Math.floor(audio.duration);
            const mins = Math.floor(cur / 60);
            const secs = cur % 60;
            timeEl.innerText = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
        }
    }

    function onVnLoadedMeta(audio) {
        const container = audio.closest('.chat-vn-player');
        if (!container) return;
        const timeEl = container.querySelector('.vn-current-time');
        if (timeEl && audio.duration && !isNaN(audio.duration)) {
            const cur = Math.floor(audio.duration);
            const mins = Math.floor(cur / 60);
            const secs = cur % 60;
            timeEl.innerText = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
        }
    }

    // Message Reactions Picker
    let currentReactionTargetWaId = null;

    function openReactionPicker(event, waMessageId) {
        event.stopPropagation();
        currentReactionTargetWaId = waMessageId;
        const bar = document.getElementById('msgReactionFloatingBar');
        if (!bar) return;

        const rect = event.currentTarget.getBoundingClientRect();
        bar.style.display = 'flex';
        const barWidth = 240;
        let left = rect.left - 60;
        if (left < 10) left = 10;
        if (left + barWidth > window.innerWidth) left = window.innerWidth - barWidth - 10;
        let top = rect.top - 48;
        if (top < 10) top = rect.bottom + 10;

        bar.style.left = left + 'px';
        bar.style.top = top + 'px';
    }

    async function selectReaction(emoji) {
        const waId = currentReactionTargetWaId;
        const bar = document.getElementById('msgReactionFloatingBar');
        if (bar) bar.style.display = 'none';
        if (!waId || !activeChatId) return;

        if (chatMessagesMap[waId]) {
            chatMessagesMap[waId].reaction = emoji;
        }
        const msgEl = document.querySelector(`.msg[data-wa-msg-id="${waId}"]`);
        if (msgEl) {
            let pill = msgEl.querySelector('.msg-reaction-pill');
            if (!pill) {
                pill = document.createElement('div');
                pill.className = 'msg-reaction-pill';
                pill.onclick = (e) => openReactionPicker(e, waId);
                msgEl.appendChild(pill);
            }
            pill.innerText = emoji;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            await fetch('/send-reaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    to: activeChatId,
                    wa_message_id: waId,
                    emoji: emoji
                })
            });
        } catch (e) {
            console.error('Reaction error:', e);
        }
    }

    // Attachment & Outgoing Media Sending
    let selectedMediaFile = null;

    function toggleAttachMenu(e) {
        e.stopPropagation();
        const menu = document.getElementById('attachMenu');
        if (menu) {
            menu.style.display = (menu.style.display === 'none' || !menu.style.display) ? 'flex' : 'none';
        }
    }

    function triggerMediaFile(accept) {
        const menu = document.getElementById('attachMenu');
        if (menu) menu.style.display = 'none';
        const fileInput = document.getElementById('mediaFileInput');
        if (fileInput) {
            fileInput.accept = accept;
            fileInput.value = '';
            fileInput.click();
        }
    }

    function handleMediaSelected(input) {
        const file = input.files?.[0];
        if (!file || !activeChatId) return;
        selectedMediaFile = file;

        const modal = document.getElementById('mediaSendModal');
        const previewBox = document.getElementById('mediaSendPreviewContainer');
        const captionInput = document.getElementById('mediaCaptionInput');
        const titleEl = document.getElementById('mediaSendTitle');
        captionInput.value = '';

        const mime = file.type || '';
        const blobUrl = URL.createObjectURL(file);

        if (mime.startsWith('image/')) {
            titleEl.innerText = 'Send Image';
            previewBox.innerHTML = `<img src="${blobUrl}" class="media-send-preview-img" alt="Preview">`;
        } else if (mime.startsWith('video/')) {
            titleEl.innerText = 'Send Video';
            previewBox.innerHTML = `<video controls playsinline src="${blobUrl}" class="media-send-preview-video"></video>`;
        } else if (mime.startsWith('audio/')) {
            titleEl.innerText = 'Send Audio';
            previewBox.innerHTML = `
                <div class="media-send-doc-box">
                    <div style="font-size: 48px;">🎵</div>
                    <div style="font-weight: 600; font-size: 16px;">${escapeHtml(file.name)}</div>
                    <audio controls src="${blobUrl}" style="margin-top: 10px; width: 100%;"></audio>
                </div>`;
        } else {
            titleEl.innerText = 'Send Document';
            const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
            previewBox.innerHTML = `
                <div class="media-send-doc-box">
                    <div style="font-size: 48px;">📄</div>
                    <div style="font-weight: 600; font-size: 16px; word-break: break-all;">${escapeHtml(file.name)}</div>
                    <div style="font-size: 13px; color: var(--text-secondary);">${sizeMb} MB</div>
                </div>`;
        }

        modal.style.display = 'flex';
        captionInput.focus();
    }

    function closeMediaSendModal() {
        const modal = document.getElementById('mediaSendModal');
        if (modal) modal.style.display = 'none';
        selectedMediaFile = null;
        const fileInput = document.getElementById('mediaFileInput');
        if (fileInput) fileInput.value = '';
    }

    async function submitSendMedia() {
        if (!selectedMediaFile || !activeChatId) return;

        const caption = document.getElementById('mediaCaptionInput').value.trim();
        const btn = document.getElementById('mediaSendConfirmBtn');
        const icon = document.getElementById('mediaSendBtnIcon');
        const spinner = document.getElementById('mediaSendSpinner');

        btn.disabled = true;
        if (icon) icon.style.display = 'none';
        if (spinner) spinner.style.display = 'block';

        const file = selectedMediaFile;
        const blobUrl = URL.createObjectURL(file);
        const tempId = 'temp_' + Date.now();
        const mime = file.type || '';
        let mediaType = 'document';
        if (mime.startsWith('image/')) mediaType = 'image';
        else if (mime.startsWith('video/')) mediaType = 'video';
        else if (mime.startsWith('audio/')) mediaType = 'audio';

        // Optimistic media bubble in chat
        let previewHtml = '';
        const isVisualMediaNoCaption = !caption && ['image', 'video'].includes(mediaType);

        if (mediaType === 'image') {
            previewHtml = `<div class="media-rendered-content">
                <img src="${blobUrl}" class="chat-media-img" style="opacity: 0.85;">
                ${isVisualMediaNoCaption ? `
                <div class="msg-meta msg-meta-floating">
                    <span class="msg-time">${formatTime(new Date())}</span>
                    <span class="msg-status"><span class="spinner" style="width:12px; height:12px; border-width:2px; border-left-color:#fff;"></span></span>
                </div>` : ''}
            </div>`;
        } else if (mediaType === 'video') {
            previewHtml = `<div class="media-rendered-content">
                <div class="media-video-container">
                    <video src="${blobUrl}" controls class="chat-media-video" style="max-height: 200px; opacity: 0.85;"></video>
                    ${isVisualMediaNoCaption ? `
                    <div class="msg-meta msg-meta-floating" style="bottom: 10px; right: 10px;">
                        <span class="msg-time">${formatTime(new Date())}</span>
                        <span class="msg-status"><span class="spinner" style="width:12px; height:12px; border-width:2px; border-left-color:#fff;"></span></span>
                    </div>` : ''}
                </div>
            </div>`;
        } else if (mediaType === 'audio') {
            previewHtml = `
                <div class="media-rendered-content">
                    <div class="chat-vn-player">
                        <button type="button" class="vn-play-circle" onclick="toggleAudioPlay(this, '${blobUrl}')">
                            <svg class="vn-icon-play" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            <svg class="vn-icon-pause" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="display:none;"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                        </button>
                        <div class="vn-track-wrapper" onclick="toggleAudioPlay(this.previousElementSibling, '${blobUrl}')">
                            <div class="vn-waveform">
                                <span class="vn-bar" style="height:35%"></span>
                                <span class="vn-bar" style="height:65%"></span>
                                <span class="vn-bar" style="height:100%"></span>
                                <span class="vn-bar" style="height:60%"></span>
                                <span class="vn-bar" style="height:85%"></span>
                                <span class="vn-bar" style="height:50%"></span>
                                <span class="vn-bar" style="height:90%"></span>
                                <span class="vn-bar" style="height:40%"></span>
                                <span class="vn-bar" style="height:75%"></span>
                                <span class="vn-bar" style="height:55%"></span>
                                <span class="vn-bar" style="height:95%"></span>
                                <span class="vn-bar" style="height:45%"></span>
                                <span class="vn-bar" style="height:80%"></span>
                                <span class="vn-bar" style="height:65%"></span>
                            </div>
                            <div class="vn-timeline"><span class="vn-current-time">0:00</span><span>Voice message</span></div>
                        </div>
                        <audio preload="metadata" src="${blobUrl}" style="display:none;" ontimeupdate="onVnTimeUpdate(this)" onended="onVnEnded(this)" onloadedmetadata="onVnLoadedMeta(this)"></audio>
                    </div>
                </div>`;
        } else {
            previewHtml = `<div class="media-placeholder-card"><span class="media-placeholder-icon">📄</span><span class="media-doc-name">${escapeHtml(file.name)}</span></div>`;
        }

        const msgHtml = `<div class="msg msg-out ${isVisualMediaNoCaption ? 'msg-media-bubble' : ''}" id="${tempId}">
            <div class="media-container">${previewHtml}</div>
            ${caption ? `<div class="media-caption"><span>${escapeHtml(caption)}</span></div>` : ''}
            ${!isVisualMediaNoCaption ? `
            <div class="msg-meta"><span class="msg-time">${formatTime(new Date())}</span><span class="msg-status"><span class="spinner" style="width:12px; height:12px; border-width:2px;"></span></span></div>
            ` : ''}
        </div>`;
        const box = document.getElementById('messageDisplay');
        box.insertAdjacentHTML('beforeend', msgHtml);
        box.scrollTop = box.scrollHeight;

        const formData = new FormData();
        formData.append('to', activeChatId);
        formData.append('file', file);
        formData.append('type', mediaType);
        if (caption) formData.append('caption', caption);

        closeMediaSendModal();

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch('/send-media', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await res.json().catch(() => null);

            const tempEl = document.getElementById(tempId);
            if (res.ok && data && data.status) {
                if (tempEl) {
                    const statusSpan = tempEl.querySelector('.msg-status');
                    if (statusSpan) statusSpan.innerHTML = getTickSVG('sent', isVisualMediaNoCaption);
                    if (data.wa_message_id) {
                        tempEl.setAttribute('data-wa-msg-id', data.wa_message_id);
                        tempEl.id = 'media-content-' + data.wa_message_id;
                    }
                    const mediaImg = tempEl.querySelector('.chat-media-img');
                    if (mediaImg) mediaImg.style.opacity = '1';
                }
            } else {
                console.error('Send media failed:', data);
                if (tempEl) {
                    const statusSpan = tempEl.querySelector('.msg-status');
                    if (statusSpan) statusSpan.innerHTML = `<span style="color:red; font-size:11px;" title="${escapeHtml(data?.error || 'Failed')}">⚠️</span>`;
                }
            }
        } catch (err) {
            console.error('Send media network error:', err);
            const tempEl = document.getElementById(tempId);
            if (tempEl) {
                const statusSpan = tempEl.querySelector('.msg-status');
                if (statusSpan) statusSpan.innerHTML = `<span style="color:red; font-size:11px;">⚠️</span>`;
            }
        } finally {
            btn.disabled = false;
            if (icon) icon.style.display = 'block';
            if (spinner) spinner.style.display = 'none';
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMediaLightbox();
            closeMediaSendModal();
            const bar = document.getElementById('msgReactionFloatingBar');
            if (bar) bar.style.display = 'none';
            const attach = document.getElementById('attachMenu');
            if (attach) attach.style.display = 'none';
        }
    });

    document.addEventListener('click', function(e) {
        const bar = document.getElementById('msgReactionFloatingBar');
        if (bar && bar.style.display !== 'none' && !bar.contains(e.target)) {
            bar.style.display = 'none';
        }
        const attachMenu = document.getElementById('attachMenu');
        if (attachMenu && attachMenu.style.display !== 'none' && !attachMenu.contains(e.target) && e.target.id !== 'attachBtn' && !e.target.closest('#attachBtn')) {
            attachMenu.style.display = 'none';
        }
    });

    function showContextMenu(e, contactId) {
        e.preventDefault(); 
        contextMenuTargetId = contactId;
        const menu = document.getElementById('contextMenu');
        menu.style.display = 'block';
        menu.style.left = e.pageX + 'px';
        menu.style.top = e.pageY + 'px';
    }

    async function contextMarkUnread() {
        if (!contextMenuTargetId) return;
        const targetId = contextMenuTargetId;
        
        document.getElementById('contextMenu').style.display = 'none';
        contextMenuTargetId = null;

        if (targetId === activeChatId) {
            goBack(); 
        }

        const contact = allContacts.find(c => c.id === targetId);
        if (contact) {
            notifiedTimestamps[targetId] = contact.time;
        }

        try {
            await db.collection('contacts').doc(targetId).update({
                unread_count: 1 
            });
        } catch (e) { console.error("Error marking as unread:", e); }
    }

    async function headerMarkUnread() {
        if (!activeChatId) return;
        const targetId = activeChatId;
        
        goBack();

        const contact = allContacts.find(c => c.id === targetId);
        if (contact) {
            notifiedTimestamps[targetId] = contact.time;
        }

        try {
            await db.collection('contacts').doc(targetId).update({
                unread_count: 1 
            });
        } catch (e) { console.error("Error marking as unread:", e); }
    }

    function openEditModal() {
        if (!activeChatId) return;
        const currentName = document.getElementById('currentChatName').innerText;
        document.getElementById('editNameInput').value = currentName;
        document.getElementById('editNameModal').style.display = 'flex';
        document.getElementById('editNameInput').focus();
    }

    function closeEditModal() { document.getElementById('editNameModal').style.display = 'none'; }

    async function saveContactName() {
        if (!activeChatId) return;
        const newName = document.getElementById('editNameInput').value;
        
        if (newName && newName.trim() !== '') {
            const cleanName = newName.trim();
            await db.collection('contacts').doc(activeChatId).update({ name: cleanName });
            
            document.getElementById('currentChatName').innerText = cleanName;
            document.getElementById('headerImg').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(cleanName)}&background=random`;
            if (cleanName !== activeChatId) {
                document.getElementById('chatStatus').innerText = activeChatId;
            }
            closeEditModal();
        }
    }

    document.getElementById('editNameInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveContactName();
        }
    });

    async function sendMessage() {
        const input = document.getElementById('messageInput');
        const text = input.value.trim();
        if (!text || !activeChatId) return;

        const msgHtml = `<div class="msg msg-out">
            <span>${cleanText(text)}</span>
            <div class="msg-meta"><span class="msg-time">${formatTime(new Date())}</span><span class="msg-status">${getTickSVG('pending')}</span></div>
        </div>`;
        const box = document.getElementById('messageDisplay');
        box.insertAdjacentHTML('beforeend', msgHtml);
        box.scrollTop = box.scrollHeight;
        
        input.value = '';
        input.style.height = 'auto'; 

        const sendBtn = document.getElementById('sendBtn');
        const micBtn = document.getElementById('micBtn');
        if (sendBtn) sendBtn.style.display = 'none';
        if (micBtn) micBtn.style.display = 'flex';
        
        try {
            await fetch('send-message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ to: activeChatId, message: text })
            });
        } catch (e) { console.error(e); }
    }

    async function openTemplateModal() {
        if (!activeChatId) return;
        
        document.getElementById('templateModal').style.display = 'flex';
        
        document.getElementById('templateList').innerHTML = '<div style="text-align:center; padding: 20px; font-size: 14px; color: var(--text-secondary);">Loading Go Templates...</div>';

        try {
            const res = await fetch('/get-templates', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            const responseJson = await res.json();
            let html = '';
            
            if (responseJson.data && responseJson.data.length > 0) {
                responseJson.data.forEach(t => {
                    html += `<button class="template-item" onclick="sendTemplateMessage('${t.name}', '${t.language || 'en_US'}', '${t.name} template')">${t.name}</button>`;
                });
            } else {
                html = '<p style="text-align:center; color:var(--text-secondary); font-size:14px;">No templates found.</p>';
            }
            document.getElementById('templateList').innerHTML = html;
        } catch(e) {
            console.error(e);
            document.getElementById('templateList').innerHTML = '<p style="text-align:center; color:red; font-size:14px;">Error loading templates.</p>';
        }
    }

    function closeTemplateModal() { document.getElementById('templateModal').style.display = 'none'; }

    async function sendTemplateMessage(templateName, language, bodyText) {
        if (!activeChatId) return;
        closeTemplateModal();

        const msgHtml = `<div class="msg msg-out">
            <span>${cleanText(bodyText)}</span>
            <div class="msg-meta"><span class="msg-time">${formatTime(new Date())}</span><span class="msg-status">${getTickSVG('pending')}</span></div>
        </div>`;
        const box = document.getElementById('messageDisplay');
        box.insertAdjacentHTML('beforeend', msgHtml);
        box.scrollTop = box.scrollHeight;

        try {
            const res = await fetch('/send-template-message', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                },
                body: JSON.stringify({ 
                    mobile: activeChatId, 
                    template_name: templateName,
                    template_language: language,
                    message_body: bodyText,
                    parameters: []
                })
            });

            const data = await res.json();

            if (!res.ok || data.status === false) {
                console.error("Backend Error:", data);
                alert("Error: " + (data.message || data.error || "Check console for details"));
            }
        } catch (e) { 
            console.error("Fetch Error:", e); 
        }
    }

    function goBack() { 
        cancelVoiceRecording();
        document.getElementById('appContainer').classList.remove('show-chat'); 
        activeChatId = null; 
        document.getElementById('defaultScreen').style.display = 'flex';
        document.getElementById('activeChatScreen').style.display = 'none';
    }
    
    const msgInput = document.getElementById('messageInput');
    
    msgInput.addEventListener("keydown", (e) => { 
        if (e.key === "Enter" && !e.shiftKey) { 
            e.preventDefault();
            sendMessage(); 
        } 
    });

    msgInput.addEventListener("input", function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        
        const sendBtn = document.getElementById('sendBtn');
        const micBtn = document.getElementById('micBtn');
        const hasText = this.value.trim().length > 0;
        if (sendBtn && micBtn) {
            sendBtn.style.display = hasText ? 'flex' : 'none';
            micBtn.style.display = hasText ? 'none' : 'flex';
        }
    });

    // ==========================================
    // WHATSAPP VOICE RECORDING INTEGRATION
    // ==========================================
    let mediaRecorder = null;
    let audioChunks = [];
    let voiceRecTimerInterval = null;
    let voiceRecSeconds = 0;
    let voiceStream = null;

    async function startVoiceRecording() {
        if (!activeChatId) {
            alert("Please select a chat first.");
            return;
        }

        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert("Audio recording is not supported in this browser or requires a secure context (HTTPS / localhost).");
                return;
            }

            voiceStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            
            let options = {};
            if (typeof MediaRecorder !== 'undefined') {
                if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
                    options = { mimeType: 'audio/webm;codecs=opus' };
                } else if (MediaRecorder.isTypeSupported('audio/ogg;codecs=opus')) {
                    options = { mimeType: 'audio/ogg;codecs=opus' };
                } else if (MediaRecorder.isTypeSupported('audio/mp4')) {
                    options = { mimeType: 'audio/mp4' };
                }
            }

            mediaRecorder = new MediaRecorder(voiceStream, options);
            audioChunks = [];

            mediaRecorder.ondataavailable = (event) => {
                if (event.data && event.data.size > 0) {
                    audioChunks.push(event.data);
                }
            };

            mediaRecorder.start(100);

            // Show recording UI, hide input & attach
            const inputEl = document.getElementById('messageInput');
            const attachWrapper = document.getElementById('footerAttachWrapper');
            const templateBtn = document.getElementById('footerTemplateBtn');
            const recBar = document.getElementById('voiceRecordBar');
            const sendBtn = document.getElementById('sendBtn');
            const micBtn = document.getElementById('micBtn');

            if (inputEl) inputEl.style.display = 'none';
            if (attachWrapper) attachWrapper.style.display = 'none';
            if (templateBtn) templateBtn.style.display = 'none';
            if (recBar) recBar.style.display = 'flex';
            if (micBtn) micBtn.style.display = 'none';
            if (sendBtn) sendBtn.style.display = 'flex';

            // Start recording timer
            voiceRecSeconds = 0;
            document.getElementById('voiceRecTimer').innerText = '0:00';
            clearInterval(voiceRecTimerInterval);
            voiceRecTimerInterval = setInterval(() => {
                voiceRecSeconds++;
                const mins = Math.floor(voiceRecSeconds / 60);
                const secs = voiceRecSeconds % 60;
                document.getElementById('voiceRecTimer').innerText = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
            }, 1000);

        } catch (err) {
            console.error("Microphone access error:", err);
            alert("Could not access microphone: " + (err.message || "Please check browser mic permissions"));
            resetVoiceRecordingUI();
        }
    }

    function cancelVoiceRecording() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.onstop = null; // Discard
            mediaRecorder.stop();
        }
        stopVoiceStream();
        resetVoiceRecordingUI();
    }

    function stopVoiceStream() {
        if (voiceStream) {
            voiceStream.getTracks().forEach(track => track.stop());
            voiceStream = null;
        }
        clearInterval(voiceRecTimerInterval);
    }

    function resetVoiceRecordingUI() {
        stopVoiceStream();
        audioChunks = [];
        const inputEl = document.getElementById('messageInput');
        const attachWrapper = document.getElementById('footerAttachWrapper');
        const templateBtn = document.getElementById('footerTemplateBtn');
        const recBar = document.getElementById('voiceRecordBar');
        const sendBtn = document.getElementById('sendBtn');
        const micBtn = document.getElementById('micBtn');

        if (inputEl) inputEl.style.display = 'block';
        if (attachWrapper) attachWrapper.style.display = 'block';
        if (templateBtn) templateBtn.style.display = 'flex';
        if (recBar) recBar.style.display = 'none';

        const hasText = inputEl ? inputEl.value.trim().length > 0 : false;
        if (sendBtn) sendBtn.style.display = hasText ? 'flex' : 'none';
        if (micBtn) micBtn.style.display = hasText ? 'none' : 'flex';
    }

    async function finishAndSendVoiceRecording() {
        if (!mediaRecorder || mediaRecorder.state === 'inactive') {
            resetVoiceRecordingUI();
            return;
        }

        const recordedSeconds = voiceRecSeconds;

        mediaRecorder.onstop = async () => {
            stopVoiceStream();

            if (audioChunks.length === 0 || recordedSeconds < 1) {
                resetVoiceRecordingUI();
                return;
            }

            const mime = mediaRecorder.mimeType || 'audio/webm';
            const cleanMime = mime.split(';')[0];
            const ext = cleanMime.includes('ogg') ? 'ogg' : (cleanMime.includes('mp4') ? 'm4a' : 'webm');

            const audioBlob = new Blob(audioChunks, { type: cleanMime });
            const audioFile = new File([audioBlob], `voice_note_${Date.now()}.${ext}`, { type: cleanMime });

            resetVoiceRecordingUI();

            selectedMediaFile = audioFile;
            await submitSendMedia();
        };

        mediaRecorder.stop();
    }

    function handleSendButtonClick() {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            finishAndSendVoiceRecording();
        } else {
            sendMessage();
        }
    }
    async function syncTemplates() {
        const syncIconBtn = document.getElementById('syncIconBtn');
        const syncStatus = document.getElementById('syncStatus');
        const svgIcon = syncIconBtn.querySelector('svg');
        
        syncIconBtn.disabled = true;
        svgIcon.classList.add('spin-anim');
        syncStatus.style.display = 'block';
        syncStatus.innerText = 'Fetching from Meta...';
        syncStatus.style.color = 'var(--text-secondary)';

        try {
            const fetchRes = await fetch('/whatsapp/fetch-missing-templates', {
                method: 'GET',
                headers: { 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const contentType = fetchRes.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Server error: Check Laravel logs (storage/logs/laravel.log) or network tab.");
            }

            const fetchData = await fetchRes.json();
            
            if (!fetchData.status) {
                throw new Error(fetchData.message || 'Fetch failed');
            }

            if (!fetchData.templates || fetchData.templates.length === 0) {
                syncStatus.innerText = 'Templates are already up to date.';
                syncStatus.style.color = 'var(--accent-green)';
            } else {
                syncStatus.innerText = `Syncing ${fetchData.templates.length} new templates...`;

                const syncRes = await fetch('/whatsapp/sync-selected-templates', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                    },
                    body: JSON.stringify({ templates: fetchData.templates })
                });

                const syncContentType = syncRes.headers.get("content-type");
                if (!syncContentType || !syncContentType.includes("application/json")) {
                    throw new Error("Server error during sync. Check backend.");
                }

                const syncData = await syncRes.json();

                if (!syncData.status) {
                    throw new Error(syncData.message || 'Sync failed');
                }

                syncStatus.innerText = syncData.message;
                syncStatus.style.color = 'var(--accent-green)';
                
                openTemplateModal();
            }
        } catch (error) {
            syncStatus.innerText = error.message;
            syncStatus.style.color = '#d32f2f';
        } finally {
            setTimeout(() => { 
                syncStatus.style.display = 'none'; 
                syncIconBtn.disabled = false;
                svgIcon.classList.remove('spin-anim');
            }, 4000);
        }
    }
</script>
</body>
</html>