<?php
session_start();

if (!isset($_SESSION["usor"])) {
    header("Location: index.php");
    exit();
}

$usor = $_SESSION["usor"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["exire"])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fabulatio - Necronomicon</title>

    <!-- Markdown Parser: Marked.js -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <!-- HTML Sanitizer: DOMPurify -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script>
    
    <!-- Math Renderer: KaTeX -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css">
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/contrib/auto-render.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=VT323&display=swap');

        :root {
            --main-color: #00FF00;
            --bg-color: #050505;
            --container-bg: #000;
            --dim-color: #008800;
            --dark-color: #003300;
            --hover-color: #002200;
            --danger-color: #ff3333;
            --danger-bg: #330000;
            --danger-hover: #ff0000;
            --warn-color: #ffff00;
        }

        html, body {
            overflow-x: hidden;
            margin: 0; padding: 0;
            width: 100%; height: 100%;
        }
        body { 
            background-color: var(--bg-color); color: var(--main-color);
            font-family: 'VT323', "Courier New", Courier, monospace; 
            box-sizing: border-box; padding: 20px;
            display: flex; justify-content: center; align-items: center;
        }

        body::after {
            content: " "; display: block; position: fixed; top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            z-index: 99; background-size: 100% 2px, 3px 100%; pointer-events: none;
        }

        .layout-wrapper {
            display: flex; gap: 20px; width: 95%; max-width: 1400px; height: 80vh;
            position: relative; z-index: 10;
        }

        /* Bookshelf Sidebar */
        .sidebar {
            width: 250px; min-width: 250px; flex-shrink: 0; border: 2px solid var(--main-color); padding: 15px; /* min-width and flex-shrink: 0 prevents sidebar from shrinking when chat expands */
            box-shadow: 0 0 20px var(--main-color), inset 0 0 10px var(--main-color); background-color: var(--container-bg);
            display: flex; flex-direction: column; height: 100%; box-sizing: border-box;
        }
        
        .sidebar h2 { margin-top: 0; text-shadow: 0 0 5px var(--main-color); border-bottom: 1px dotted var(--main-color); padding-bottom: 10px; }

        .chat-list { list-style: none; padding: 0; flex-grow: 1; overflow-y: auto; margin-top: 0; }
        
        .chat-item {
            padding: 10px; border: 1px dashed var(--dim-color); margin-bottom: 10px; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center; font-size: 20px;
        }
        
        .chat-item:hover, .chat-item.active { background-color: var(--hover-color); border-style: solid; border-color: var(--main-color); }
        
        .chat-item-name { flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;}
        
        .action-btns { display: flex; gap: 5px; }
        .ren-btn { color: #ffff33; cursor: pointer; border: none; background: none; font-family: inherit; font-size: 20px; text-shadow: 0 0 2px yellow;}
        .ren-btn:hover { color: #ffff00; font-weight: bold; background: #333300;}
        .del-btn { color: var(--danger-color); cursor: pointer; border: none; background: none; font-family: inherit; font-size: 20px; text-shadow: 0 0 2px red;}
        .del-btn:hover { color: var(--danger-hover); font-weight: bold; background: var(--danger-bg);}

        /* Main Chat Window */
        .main-chat {
            flex-grow: 1; border: 2px solid var(--main-color); padding: 30px;
            box-shadow: 0 0 20px var(--main-color), inset 0 0 10px var(--main-color); background-color: var(--container-bg);
            display: flex; flex-direction: column; overflow: hidden; height: 100%; box-sizing: border-box;
            min-width: 0; min-height: 0; /* min-width: 0 and min-height: 0 prevents flex overflow/expanding beyond screen limits */
        }

        h1 { font-size: 36px; text-shadow: 0 0 5px var(--main-color); margin-top: 0;}
        
        input[type="text"], input[type="submit"], button { 
            background-color: var(--container-bg); color: var(--main-color); border: 1px solid var(--main-color); padding: 10px;
            font-family: 'VT323', "Courier New", Courier, monospace; font-size: 24px;
        }
        input[type="submit"]:hover, button:hover { background-color: var(--main-color); color: var(--container-bg); cursor: pointer; }
        input[type="submit"]:disabled, input[type="text"]:disabled { opacity: 0.5; cursor: not-allowed; }
        
        #chat { 
            width: 100%; flex-grow: 1; border: 1px solid var(--main-color); overflow-y: auto; overflow-x: hidden;
            padding: 15px; margin-bottom: 20px;
            box-sizing: border-box; font-size: 22px;
            box-shadow: inset 0 0 10px var(--main-color); scroll-behavior: smooth;
            display: flex; flex-direction: column; min-height: 0; /* min-height: 0 prevents flex child overflow */
        }

        .msg-user {
            background-color: var(--dark-color); border: 1px solid var(--main-color);
            padding: 10px 15px; margin: 5px 0 15px 0; align-self: flex-end;
            border-radius: 10px 10px 0 10px; color: var(--main-color);
            box-shadow: 0 0 10px var(--dark-color); max-width: 85%;
            word-wrap: break-word; overflow-wrap: break-word;
        }

        .msg-oracle {
            background-color: var(--container-bg); border: 1px dashed var(--dim-color);
            padding: 10px 15px; margin: 5px 0 15px 0; align-self: flex-start;
            border-radius: 10px 10px 10px 0; color: var(--main-color); max-width: 85%;
            word-wrap: break-word; overflow-wrap: break-word;
        }

        
        #chat p { margin: 0 0 10px 0; }
        #chat pre {
            background-color: var(--dark-color); border: 1px dotted var(--main-color);
            padding: 10px; overflow-x: auto;
            font-family: inherit; font-size: inherit; text-shadow: none;
        }
        #chat code {
            background-color: var(--dark-color); color: var(--warn-color);
            padding: 2px 5px; font-family: inherit; font-size: 0.9em;
        }
        #chat ul, #chat ol { margin: 0 0 10px 0; padding-left: 25px; }
        #chat strong { color: #fff; text-shadow: 0 0 5px var(--main-color); }

        .reasoning-details {
            margin: 5px 0 15px 0;
            padding: 8px 12px;
            border: 1px dashed var(--dim-color);
            background: rgba(0, 0, 0, 0.3);
            font-family: 'Courier New', Courier, monospace;
        }
        .reasoning-details summary {
            cursor: pointer;
            color: var(--dim-color);
            font-style: italic;
            font-size: 14px;
            outline: none;
            user-select: none;
        }
        .reasoning-details summary:hover {
            color: var(--main-color);
            text-shadow: 0 0 5px var(--main-color);
        }
        .reasoning-content {
            margin-top: 10px;
            color: var(--dim-color);
            opacity: 0.9;
            font-size: 16px;
            border-top: 1px dotted var(--dark-color);
            padding-top: 8px;
        }

        .tool-text {
            color: var(--warn-color); font-weight: bold; font-style: italic;
            background-color: var(--hover-color); padding: 2px 5px; margin: 2px 0;
            border: 1px dashed var(--warn-color); display: inline-block;
        }

        .toggles-bar {
            display: flex; gap: 15px; margin-bottom: 10px; align-items: center;
            border-top: 1px dotted var(--dim-color); padding-top: 10px;
        }
        .toggle-btn {
            font-size: 18px; padding: 5px 12px; min-width: 140px; text-align: center;
        }
        .toggle-active {
            background-color: var(--main-color) !important; color: var(--container-bg) !important;
            box-shadow: 0 0 10px var(--main-color);
        }
        .toggle-inactive {
            color: var(--dim-color); border-color: var(--dim-color);
        }
        
        .blink { animation: blink-animation 1s steps(5, start) infinite; -webkit-animation: blink-animation 1s steps(5, start) infinite; }
        @keyframes blink-animation { to { visibility: hidden; } }
        @-webkit-keyframes blink-animation { to { visibility: hidden; } }

        /* Welcome Modal Styles */
        #welcome-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: var(--container-bg); z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            opacity: 1; transition: opacity 0.5s ease;
        }
        .welcome-content { text-align: center; color: var(--main-color); }
        .welcome-text {
            font-size: 36px; font-weight: bold; overflow: hidden; white-space: pre-wrap; margin: 0 auto;
            border-right: .15em solid var(--main-color); animation: blink-caret .75s step-end infinite;
        }

        /* New Chat Modal Styles */
        #new-chat-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85); z-index: 9998;
            display: none; justify-content: center; align-items: center;
        }
        .new-chat-content {
            border: 2px solid var(--main-color); padding: 30px; text-align: center;
            background-color: var(--container-bg); box-shadow: 0 0 20px var(--main-color);
        }
        #new-chat-input, #rename-chat-input { width: 80%; margin-bottom: 20px; text-align: center; }
        .cancel-btn { background-color: var(--danger-bg); color: var(--danger-color); border-color: var(--danger-color); }
        .cancel-btn:hover { background-color: var(--danger-color); color: #fff; }

        /* Rename Chat Modal Styles */
        #rename-chat-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85); z-index: 9998;
            display: none; justify-content: center; align-items: center;
        }
        .rename-chat-content {
            border: 2px solid var(--main-color); padding: 30px; text-align: center;
            background-color: var(--container-bg); box-shadow: 0 0 20px var(--main-color);
        }

        /* Delete Chat Modal Styles */
        #delete-chat-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85); z-index: 9998;
            display: none; justify-content: center; align-items: center;
        }
        .delete-chat-content {
            border: 2px solid #ff0000; padding: 30px; text-align: center;
            background-color: #000; box-shadow: 0 0 20px #ff0000;
        }

        /* Config Modal Styles */
        #config-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85); z-index: 9999;
            display: none; justify-content: center; align-items: center;
        }
        .config-content {
            border: 2px solid var(--main-color); padding: 30px; text-align: left;
            background-color: var(--container-bg); box-shadow: 0 0 20px var(--main-color);
            width: 80%; max-width: 600px;
            max-height: 90vh; overflow-y: auto;
        }
        .config-section {
            margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px dotted var(--main-color);
        }
        .config-section:last-child {
            border-bottom: none; margin-bottom: 0; padding-bottom: 0;
        }
        .config-input, .config-select {
            width: 100%; margin-bottom: 10px; margin-top: 5px; box-sizing: border-box;
            background-color: var(--container-bg); color: var(--main-color); border: 1px solid var(--main-color); padding: 10px;
            font-family: inherit; font-size: 20px;
        }
        .config-btn { margin-top: 10px; }
        .danger-btn { background-color: var(--danger-bg); color: var(--danger-color); border-color: var(--danger-color); }
        .danger-btn:hover { background-color: var(--danger-hover); color: #fff; }

        /* Glitches - Applied conditionally */
        body.glitch-shake .sidebar {
            animation: rare-shake 8s infinite;
            transform: translate3d(0, 0, 0);
        }
        body.glitch-shake .main-chat {
            animation: rare-shake-alt 11s infinite;
            transform: translate3d(0, 0, 0);
        }
        @keyframes rare-shake {
            0%, 94% { transform: translate3d(0, 0, 0); }
            95% { transform: translate3d(-2px, 1px, 0); }
            96% { transform: translate3d(3px, -1px, 0); }
            97% { transform: translate3d(-4px, 2px, 0); }
            98% { transform: translate3d(4px, -2px, 0); }
            99% { transform: translate3d(-2px, 1px, 0); }
            100% { transform: translate3d(0, 0, 0); }
        }
        @keyframes rare-shake-alt {
            0%, 95% { transform: translate3d(0, 0, 0); }
            96% { transform: translate3d(2px, -1px, 0); }
            97% { transform: translate3d(-3px, 1px, 0); }
            98% { transform: translate3d(4px, -2px, 0); }
            99% { transform: translate3d(-4px, 2px, 0); }
            100% { transform: translate3d(0, 0, 0); }
        }

        body.glitch-chromatic * {
            text-shadow: 2px 0px #f00, -2px 0px #0ff;
        }

        /* NEW BACKGROUND EFFECTS */
        body.glitch-scanlines::before {
            content: " "; display: block; position: fixed;
            top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            z-index: 1000; background-size: 100% 2px, 3px 100%; pointer-events: none;
        }

        body.glitch-vignette::after {
            content: " "; display: block; position: fixed;
            top: 0; left: 0; bottom: 0; right: 0;
            background: radial-gradient(circle, rgba(0,0,0,0) 60%, rgba(0,0,0,0.8) 100%);
            z-index: 999; pointer-events: none;
            animation: vignette-breathe 8s ease-in-out infinite alternate;
        }
        @keyframes vignette-breathe {
            0% { opacity: 0.8; }
            100% { opacity: 1; }
        }

        body.glitch-pulse::after {
            content: " "; display: block; position: fixed;
            top: 50%; left: 50%; width: 100vw; height: 100vw;
            margin-top: -50vw; margin-left: -50vw;
            background: radial-gradient(circle, var(--main-color) 0%, rgba(0,0,0,0) 60%);
            border-radius: 50%; opacity: 0; pointer-events: none; z-index: -3;
            animation: pulse-infernal 6s ease-out infinite; mix-blend-mode: screen;
        }
        @keyframes pulse-infernal {
            0% { transform: scale(0.1); opacity: 0.2; }
            100% { transform: scale(2.0); opacity: 0; }
        }

        body.glitch-fog .fog-overlay { display: block; }
        .fog-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><filter id="noiseFilter"><feTurbulence type="fractalNoise" baseFrequency="0.01" numOctaves="3" stitchTiles="stitch"/></filter><rect width="100%" height="100%" filter="url(%23noiseFilter)" opacity="0.1" fill="%2300ff00" /></svg>');
            opacity: 0.15; z-index: -1; pointer-events: none;
            animation: fog-move 20s linear infinite;
        }
        @keyframes fog-move {
            0% { background-position: 0 0; }
            100% { background-position: 100% 100%; }
        }

        #matrixCanvas {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -2; display: none; pointer-events: none;
        }
        body.glitch-matrix #matrixCanvas { display: block; }


        body.glitch-borders .sidebar, body.glitch-borders .main-chat, body.glitch-borders .config-content {
            animation: border-glitch 2s linear infinite;
        }
        @keyframes border-glitch {
            0% { border-style: solid; border-width: 2px; }
            25% { border-style: dashed; border-width: 4px; }
            50% { border-style: dotted; border-width: 1px; }
            75% { border-style: double; border-width: 5px; }
            100% { border-style: solid; border-width: 2px; }
        }

        body.glitch-noise::before {
            content: " "; display: block; position: fixed; top: 0; left: 0; bottom: 0; right: 0;
            background: url('data:image/svg+xml,%3Csvg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"%3E%3Cfilter id="noiseFilter"%3E%3CfeTurbulence type="fractalNoise" baseFrequency="0.65" numOctaves="3" stitchTiles="stitch"/%3E%3C/filter%3E%3Crect width="100%25" height="100%25" filter="url(%23noiseFilter)" opacity="0.15"/%3E%3C/svg%3E');
            z-index: 100; pointer-events: none; opacity: 0.15;
            animation: noise 0.2s infinite alternate;
        }
        @keyframes noise {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-5px, 5px); }
        }



        .guest-pacman {
            position: fixed; bottom: 10px; left: -250px; width: 250px; height: 35px;
            background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjMwIiB2aWV3Qm94PSIwIDAgMjAwIDMwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgogIDxnIHRyYW5zZm9ybT0idHJhbnNsYXRlKDE1LCAxNSkiPgogICAgPHBhdGggZmlsbD0ieWVsbG93Ij4KICAgICAgPGFuaW1hdGUgYXR0cmlidXRlTmFtZT0iZCIgdmFsdWVzPSJNMCwwIEwxMCwtMTAgQTE0LDE0IDAgMSwxIDEwLDEwIFo7IE0wLDAgTDE0LC0yIEExNCwxNCAwIDEsMSAxNCwyIFo7IE0wLDAgTDEwLC0xMCBBMTQsMTQgMCAxLDEgMTAsMTAgWiIgZHVyPSIwLjRzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSIgLz4KICAgIDwvcGF0aD4KICA8L2c+CiAgPGcgZmlsbD0icmVkIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg2MCwgNSkiPgogICAgPHBhdGggZD0iTTAsMjAgTDAsMTAgQTEwLDEwIDAgMCwxIDIwLDEwIEwyMCwyMCBMMTYsMTYgTDEyLDIwIEw4LDE2IEw0LDIwIFoiIC8+CiAgICA8Y2lyY2xlIGN4PSI2IiBjeT0iMTAiIHI9IjMiIGZpbGw9IndoaXRlIi8+PGNpcmNsZSBjeD0iNiIgY3k9IjEwIiByPSIxLjUiIGZpbGw9ImJsdWUiLz4KICAgIDxjaXJjbGUgY3g9IjE0IiBjeT0iMTAiIHI9IjMiIGZpbGw9IndoaXRlIi8+PGNpcmNsZSBjeD0iMTQiIGN5PSIxMCIgcj0iMS41IiBmaWxsPSJibHVlIi8+CiAgPC9nPgogIDxnIGZpbGw9ImN5YW4iIHRyYW5zZm9ybT0idHJhbnNsYXRlKDkwLCA1KSI+CiAgICA8cGF0aCBkPSJNMCwyMCBMMCwxMCBBMTAsMTAgMCAwLDEgMjAsMTAgTDIwLDIwIEwxNiwxNiBMMTIsMjAgTDgsMTYgTDQsMjAgWiIgLz4KICAgIDxjaXJjbGUgY3g9IjYiIGN5PSIxMCIgcj0iMyIgZmlsbD0id2hpdGUiLz48Y2lyY2xlIGN4PSI2IiBjeT0iMTAiIHI9IjEuNSIgZmlsbD0iYmx1ZSIvPgogICAgPGNpcmNsZSBjeD0iMTQiIGN5PSIxMCIgcj0iMyIgZmlsbD0id2hpdGUiLz48Y2lyY2xlIGN4PSIxNCIgY3k9IjEwIiByPSIxLjUiIGZpbGw9ImJsdWUiLz4KICA8L2c+CiAgPGcgZmlsbD0icGluayIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTIwLCA1KSI+CiAgICA8cGF0aCBkPSJNMCwyMCBMMCwxMCBBMTAsMTAgMCAwLDEgMjAsMTAgTDIwLDIwIEwxNiwxNiBMMTIsMjAgTDgsMTYgTDQsMjAgWiIgLz4KICAgIDxjaXJjbGUgY3g9IjYiIGN5PSIxMCIgcj0iMyIgZmlsbD0id2hpdGUiLz48Y2lyY2xlIGN4PSI2IiBjeT0iMTAiIHI9IjEuNSIgZmlsbD0iYmx1ZSIvPgogICAgPGNpcmNsZSBjeD0iMTQiIGN5PSIxMCIgcj0iMyIgZmlsbD0id2hpdGUiLz48Y2lyY2xlIGN4PSIxNCIgY3k9IjEwIiByPSIxLjUiIGZpbGw9ImJsdWUiLz4KICA8L2c+CiAgPGcgZmlsbD0ib3JhbmdlIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxNTAsIDUpIj4KICAgIDxwYXRoIGQ9Ik0wLDIwIEwwLDEwIEExMCwxMCAwIDAsMSAyMCwxMCBMMjAsMjAgTDE2LDE2IEwxMiwyMCBMOCwxNiBMNCwyMCBaIiAvPgogICAgPGNpcmNsZSBjeD0iNiIgY3k9IjEwIiByPSIzIiBmaWxsPSJ3aGl0ZSIvPjxjaXJjbGUgY3g9IjYiIGN5PSIxMCIgcj0iMS41IiBmaWxsPSJibHVlIi8+CiAgICA8Y2lyY2xlIGN4PSIxNCIgY3k9IjEwIiByPSIzIiBmaWxsPSJ3aGl0ZSIvPjxjaXJjbGUgY3g9IjE0IiBjeT0iMTAiIHI9IjEuNSIgZmlsbD0iYmx1ZSIvPgogIDwvZz4KPC9zdmc+') no-repeat center center;
            background-size: contain; z-index: 100; pointer-events: none; opacity: 0;
            animation: pacman-run 20s linear infinite; filter: drop-shadow(0 0 10px rgba(255, 255, 0, 0.5));
        }
        @keyframes pacman-run {
            0%, 75% { left: -200px; opacity: 0; }
            76% { opacity: 0.9; }
            98% { opacity: 0.9; }
            100% { left: 110vw; opacity: 0; }
        }

        .guest-mk2 {
            position: fixed; bottom: -150px; right: 5%; width: 150px; height: 150px;
            background: url('data:image/png;base64,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') no-repeat bottom center;
            background-size: contain; z-index: 150; pointer-events: none; opacity: 0;
            animation: mk2-toasty 30s ease-in-out infinite; 
            filter: drop-shadow(0 0 5px var(--main-color));
        }
        @keyframes mk2-toasty {
            0%, 94% { bottom: -150px; opacity: 0; transform: rotate(0deg); }
            95% { bottom: 0px; opacity: 1; transform: rotate(-5deg); }
            98% { bottom: 0px; opacity: 1; transform: rotate(0deg); }
            100% { bottom: -150px; opacity: 0; }
        }


        body.show-pacman .guest-pacman { display: block; }
        body:not(.show-pacman) .guest-pacman { display: none; }
        body.show-mk2 .guest-mk2 { display: block; }
        body:not(.show-mk2) .guest-mk2 { display: none; }

        #advCanvas {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1; pointer-events: none;
        }
    </style>
</head>
<body>

    <div class="guest-pacman"></div>
    <div class="guest-mk2"></div>

    <canvas id="matrixCanvas"></canvas>
    <canvas id="advCanvas"></canvas>
    <div class="fog-overlay"></div>
    <div id="welcome-modal">
        <div class="welcome-content">
            <div class="welcome-text" id="welcome-typewriter"></div>
        </div>
    </div>


    <!-- Rename Chat Modal -->
    <div id="rename-chat-modal">
        <div class="rename-chat-content">
            <h2 style="margin-top: 0; text-shadow: 0 0 5px #00FF00;">Renominare Fabulationem</h2>
            <p style="font-size: 24px;">Novum nomen pro: <span id="rename-room-name" style="color:#ffff00;"></span>?</p>
            <input type="text" id="rename-chat-input" autocomplete="off" onkeydown="if(event.key === 'Enter') submitRenameChat()">
            <div style="margin-top: 20px;">
                <button onclick="submitRenameChat()" style="margin-right: 15px;">Renominare (Rename)</button>
                <button onclick="closeRenameModal()" class="cancel-btn">Inducere (Cancel)</button>
            </div>
        </div>
    </div>

    <!-- Delete Chat Modal -->
    <div id="delete-chat-modal">
        <div class="delete-chat-content">
            <h2 style="margin-top: 0; color: #ff0000; text-shadow: 0 0 5px #ff0000;">Delere Fabulationem</h2>
            <p style="font-size: 24px; color: #ff3333;">Visne vere delere fabulationem: <span id="delete-room-name" style="color:#ffff00;"></span>?</p>
            <div style="margin-top: 20px;">
                <button onclick="submitDeleteChat()" class="cancel-btn" style="margin-right: 15px;">Ita, Delere (Yes, Delete)</button>
                <button onclick="closeDeleteModal()">Inducere (Cancel)</button>
            </div>
        </div>
    </div>

    <!-- Configuration Modal -->
    <div id="config-modal">
        <div class="config-content">
            <h2 style="margin-top: 0; text-shadow: 0 0 5px #00FF00; text-align: center;">Configuratio Rationis (Account Settings)</h2>
            <div id="config-alert" style="color: #ffff00; text-align: center; margin-bottom: 15px; font-weight: bold;"></div>

            <div class="config-section" id="level-display" style="display: none;">
                <!-- Level system hidden -->
            </div>

            <div class="config-section">
                <h3>Thema et Effectus (Theme & Effects)</h3>
                <select id="config-theme" class="config-select" onchange="previewTheme()">
                    <option value="0">0. Viridis (Classic Green)</option>
                    <!-- More options populated via JS -->
                </select>

                <div id="glitches-container" style="margin-top: 15px;">
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-10" onchange="previewGlitches()"> Terraemotus (Shake)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-11" onchange="previewGlitches()"> Aberratio (Chromatic)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-12" onchange="previewGlitches()"> Fines fracti (Broken Borders)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-13" onchange="previewGlitches()"> Caligo (CRT Noise)</label>
                    <hr style="border-color: var(--dim-color); margin: 10px 0;">
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-20" onchange="previewGlitches()"> Lineae Cathodicae (CRT Scanlines)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-21" onchange="previewGlitches()"> Tenebrae Spirantes (Breathing Shadows)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-22" onchange="previewGlitches()"> Imber Codicis (Matrix Rain)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-23" onchange="previewGlitches()"> Nebula Obscura (Dark Fog)</label>
                    <hr style="border-color: var(--dim-color); margin: 10px 0;">
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-24" onchange="previewGlitches()"> Sanguis Stillans (Dripping Blood)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-25" onchange="previewGlitches()"> Astrum Cadens (Space Flight)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-26" onchange="previewGlitches()"> Oculi Vigilantes (Watching Eyes)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-27" onchange="previewGlitches()"> Reticulum Araneae (Interactive Web)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-28" onchange="previewGlitches()"> Pulsus Infernalis (Infernal Pulse)</label>
                    <hr style="border-color: var(--dim-color); margin: 10px 0;">
                    <h4 style="margin: 5px 0; color: #aaa;">Hospites (Guests / Characters)</h4>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-31" onchange="previewGlitches()"> Hospes: Pac-Man (Chased in the abyss)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-32" onchange="previewGlitches()"> Hospes: Toasty (MK2 pop-up guy)</label>
                    <hr style="border-color: var(--dim-color); margin: 10px 0;">
                    <h4 style="margin: 5px 0; color: #aaa;">Soni (Sounds)</h4>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-33" onchange="previewGlitches()"> Soni Extincti (Mute Clicks)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-34" onchange="previewGlitches()"> Melodia Octo-Bit (8-Bit Melody)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-35" onchange="previewGlitches()"> Claves Mechanicae (Mechanical Keyboard)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-36" onchange="previewGlitches()"> Ventus Obscurus (Ambient Wind)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-37" onchange="previewGlitches()"> Murmur Antiquum (50Hz Hum)</label>
                </div>
                <button class="config-btn" onclick="saveVisualOptions()" style="margin-top: 15px;">Servare (Save)</button>
            </div>

            <div class="config-section">
                <h3>Renominare Usorem (Rename User)</h3>
                <input type="text" id="config-new-name" class="config-input" placeholder="Novum Nomen...">
                <button class="config-btn" onclick="renameUser()">Renominare</button>
            </div>

            <div class="config-section">
                <h3>Mutare Tessellam (Change Password) <span style="font-size: 16px; color: #008800;">- Tantum pro ANIMA (Only for Email users)</span></h3>
                <input type="password" id="config-old-pass" class="config-input" placeholder="Vetus Tessella (Old Password)...">
                <input type="password" id="config-new-pass" class="config-input" placeholder="Nova Tessella (New Password)...">
                <button class="config-btn" onclick="changePassword()">Mutare</button>
            </div>

            <div class="config-section" style="border-top: 2px dashed #ff0000; padding-top: 15px;">
                <h3 style="color: #ff3333; margin-top: 0;">Zona Periculosa (Danger Zone)</h3>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button class="config-btn danger-btn" onclick="deleteAllChats()">[!] Delere Omnes Fabulationes</button>
                    <button class="config-btn danger-btn" onclick="deleteAccount()">[!!!] Delere Rationem (Delete Account)</button>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <button onclick="closeConfigModal()">Inducere (Close)</button>
            </div>
        </div>
    </div>

    <div class="layout-wrapper">
        <div class="sidebar">
            <h2>Pluteus (Chats)</h2>
            <button onclick="createNewChat()" style="margin-bottom: 15px; background-color: #003300;">+ Nova Fabulatio</button>
            <ul class="chat-list" id="chat-list">
                <!-- Chats will load here via JS -->
            </ul>
            <div style="margin-top: auto; display: flex; flex-direction: column; gap: 10px;">
                <button onclick="openConfigModal()" style="width: 100%; background: var(--container-bg); color: var(--main-color); border: 1px dashed var(--main-color); text-align: left; padding: 10px;">⚙ Configuratio (Settings)</button>
                <form method="POST" action="fabulatio.php" style="margin: 0;">
                    <input type="submit" name="exire" value="Exire (Logout)" style="width:100%; background: var(--danger-bg); color: var(--danger-color); border-color: var(--danger-color);">
                </form>
            </div>
        </div>

        <div class="main-chat">
            <h1>Forum: <?php echo htmlspecialchars($usor); ?> <span class="blink">_</span> <span id="current-room-label" style="font-size: 24px; color: var(--dim-color); float: right;"></span></h1>
            <div id="chat">Eligere fabulationem e pluteo...</div>

            <div class="toggles-bar">
                <button id="toggle-lang" class="toggle-btn" onclick="toggleLanguage()">Lingua: Latina [L]</button>
                <button id="toggle-search" class="toggle-btn" onclick="toggleSearch()">Investigatio: OFF [-]</button>
                <span id="toggles-info" style="font-size: 14px; color: #006600; font-family: monospace; flex-grow: 1; text-align: right;">MODUS: TRADITIO</span>
            </div>

            <form id="chat-form" style="display: flex; gap: 10px;">
                <input type="text" id="nuntius" name="nuntius" style="flex-grow: 1;" autocomplete="off" placeholder="Dicent..." disabled>
                <input type="submit" id="send-btn" value="Mittere (Send)" disabled>
            </form>
        </div>
    </div>

    <script>
        let currentRoom = '';
        let virtualRooms = []; // Newly created rooms without history yet
        const chatEl = document.getElementById("chat");
        const chatListEl = document.getElementById("chat-list");
        const chatForm = document.getElementById("chat-form");
        const nuntiusInput = document.getElementById("nuntius");
        const sendBtn = document.getElementById("send-btn");
        const roomLabel = document.getElementById("current-room-label");

        // Retro keystroke sound effects for the main input field
        nuntiusInput.addEventListener('keydown', function(e) {
            if (e.repeat) return;
            if (typeof initHumSound === 'function') initHumSound();
            if (typeof initWindSound === 'function') initWindSound();
            if (typeof initMelody === 'function') initMelody();
            if (document.body.classList.contains('mute-sounds')) return;
            if (e.key.length === 1 || e.key === 'Backspace' || e.key === 'Delete' || e.key === 'Enter') {
                if (document.body.classList.contains('mech-clicks')) {
                    if (typeof playMechClickSound === 'function') playMechClickSound();
                } else {
                    if (typeof playClickSound === 'function') playClickSound();
                }
            }
        });

        // Welcome Animation Logic
        const welcomeText = "CONEXIO STABILITA...\nSALVE, <?php echo htmlspecialchars($usor); ?>.\nORACULUM TE EXSPECTAT.";
        const typeEl = document.getElementById("welcome-typewriter");
        const modalEl = document.getElementById("welcome-modal");
        let typeI = 0;

        function typeWriterWelcome() {
            if (typeI < welcomeText.length) {
                typeEl.innerHTML += welcomeText.charAt(typeI) === '\n' ? '<br/>' : welcomeText.charAt(typeI);
                typeI++;
                setTimeout(typeWriterWelcome, 40);
            } else {
                setTimeout(closeWelcomeModal, 800);
            }
        }

        function closeWelcomeModal() {
            modalEl.style.opacity = '0';
            setTimeout(() => { modalEl.style.display = 'none'; }, 500);
        }

        window.onload = () => {
            loadChats(true);
            typeWriterWelcome();
        };

        modalEl.addEventListener('click', closeWelcomeModal);
        
        // Close Modals on Outside Click
        window.addEventListener('click', function(event) {
            const configModal = document.getElementById('config-modal');
            const renameModal = document.getElementById('rename-chat-modal');
            const deleteModal = document.getElementById('delete-chat-modal');
            
            if (event.target === configModal) closeConfigModal();
            if (event.target === renameModal) closeRenameModal();
            if (event.target === deleteModal) closeDeleteModal();
        });

        // AJAX Chat Logic
        function loadChats(selectDefault = false) {
            fetch('api.php?action=list&t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    chatListEl.innerHTML = '';
                    let rooms = data.rooms || [];
                    if (rooms.length === 1 && rooms[0] === "") rooms = [];
                    
                    // Merge physical rooms with virtual unsaved rooms
                    virtualRooms.forEach(vr => {
                        if (!rooms.includes(vr)) rooms.unshift(vr);
                    });
                    
                    // If current room still isn't in there (e.g. just renamed before refresh), add it
                    if (currentRoom && currentRoom !== '' && !rooms.includes(currentRoom)) {
                         rooms.unshift(currentRoom);
                    }

                    if (rooms.length > 0) {
                        rooms.forEach(room => {
                            const li = document.createElement('li');
                            li.className = 'chat-item' + (room === currentRoom ? ' active' : '');
                            li.onclick = () => selectRoom(room);
                            li.innerHTML = `<span class="chat-item-name" title="${room}">${room}</span> 
                                          <div class="action-btns">
                                              <button class="ren-btn" onclick="renameRoom('${room}', event)" title="Renominare">[R]</button>
                                              <button class="del-btn" onclick="deleteRoom('${room}', event)" title="Delere">[X]</button>
                                          </div>`;
                            chatListEl.appendChild(li);
                        });
                    }
                    if (selectDefault) {
                        const roomToSelect = rooms.length > 0 ? rooms[0] : 'default';
                        selectRoom(roomToSelect, false); // Don't refresh sidebar again during selection
                    }
                });
        }

        function selectRoom(room, refreshSidebar = true) {
            if (currentRoom === room && !refreshSidebar) return; 
            currentRoom = room;
            roomLabel.textContent = `[${room}]`;
            nuntiusInput.disabled = false;
            sendBtn.disabled = false;
            
            // Visual Update Without Full Fetch Before Load
            Array.from(chatListEl.children).forEach(li => {
                const nameEl = li.querySelector('.chat-item-name');
                if (nameEl) {
                    li.classList.toggle('active', nameEl.textContent === room);
                }
            });

            if (refreshSidebar) {
                // Ensure the list shows the currentRoom even if it's virtual
                loadChats(false);
            }

            fetch('api.php?action=load&room=' + encodeURIComponent(room) + '&t=' + Date.now())
                .then(r => r.text())
                .then(text => {
                    if (!text || text.trim() === '') {
                        chatEl.innerHTML = 'Nihil scriptum est...';
                        return;
                    }
                    
                    chatEl.innerHTML = '';
                    const messageBlocks = text.split(/(?=^Tute:|^Oraculum:)/m);
                    
                    messageBlocks.forEach(block => {
                        if (!block.trim()) return;
                        
                        const isUser = block.startsWith('Tute:');
                        const isOracle = block.startsWith('Oraculum:');
                        
                        let cleanBlock = block;
                        const msgDiv = document.createElement('div');
                        msgDiv.className = isUser ? 'msg-user' : 'msg-oracle';

                        if (isOracle) {
                            cleanBlock = block.replace(/^Oraculum:\s*/, '');
                            let thoughtMatch = cleanBlock.match(/<thought>(.*?)<\/thought>(.*)/s);
                            if (thoughtMatch) {
                                let thought = thoughtMatch[1];
                                let actualMsg = thoughtMatch[2];
                                msgDiv.innerHTML = `<strong>Oraculum: </strong>
                                    <details class="reasoning-details">
                                        <summary>Cogitationes Oraculi...</summary>
                                        <div class="reasoning-content">${DOMPurify.sanitize(marked.parse(thought))}</div>
                                    </details>
                                    <div>${DOMPurify.sanitize(marked.parse(actualMsg))}</div>`;
                            } else {
                                msgDiv.innerHTML = DOMPurify.sanitize(marked.parse('**Oraculum:** ' + cleanBlock));
                            }
                        } else {
                            cleanBlock = block.replace(/^Tute:\s*/, '');
                            msgDiv.innerHTML = DOMPurify.sanitize(marked.parse('**Tute:** ' + cleanBlock));
                        }
                        
                        renderMathInElement(msgDiv, {
                            delimiters: [
                                {left: '$$', right: '$$', display: true},
                                {left: '\\[', right: '\\]', display: true},
                                {left: '$', right: '$', display: false},
                                {left: '\\(', right: '\\)', display: false}
                            ],
                            throwOnError: false
                        });
                        chatEl.appendChild(msgDiv);
                    });
                    
                    chatEl.scrollTop = chatEl.scrollHeight;
                })
                .catch(err => console.error("Error loading chat:", err));
        }

        function createNewChat() {
            let tempName = "Nova_Fabulatio";
            let counter = 1;
            while (virtualRooms.includes(tempName) || Array.from(chatListEl.children).some(li => {
                const nameEl = li.querySelector('.chat-item-name');
                return nameEl && nameEl.textContent === tempName;
            })) {
                tempName = "Nova_Fabulatio_" + counter;
                counter++;
            }
            virtualRooms.push(tempName);
            selectRoom(tempName);
        }

        let roomToRename = '';

        function renameRoom(room, event) {
            event.stopPropagation();
            roomToRename = room;
            document.getElementById('rename-room-name').textContent = room;
            document.getElementById('rename-chat-modal').style.display = 'flex';
            document.getElementById('rename-chat-input').value = room;
            document.getElementById('rename-chat-input').select();
        }

        function closeRenameModal() {
            document.getElementById('rename-chat-modal').style.display = 'none';
        }

        function submitRenameChat() {
            const newName = document.getElementById('rename-chat-input').value.trim();
            if (newName && newName !== roomToRename) {
                const safeName = newName.replace(/[^a-zA-Z0-9_-]/g, '');
                if (safeName) {
                    const formData = new URLSearchParams();
                    formData.append('action', 'rename');
                    formData.append('room', roomToRename);
                    formData.append('new_room', safeName);
                    
                    fetch('api.php', { method: 'POST', body: formData })
                        .then(() => {
                            if (currentRoom === roomToRename) {
                                currentRoom = safeName;
                                roomLabel.textContent = `[${currentRoom}]`;
                            }
                            loadChats();
                            closeRenameModal();
                        });
                }
            } else {
                closeRenameModal();
            }
        }

        let roomToDelete = '';

        function deleteRoom(room, event) {
            event.stopPropagation();
            roomToDelete = room;
            const roomSpan = document.getElementById('delete-room-name');
            if (roomSpan) roomSpan.textContent = room;
            const modal = document.getElementById('delete-chat-modal');
            if (modal) modal.style.display = 'flex';
        }

        function closeDeleteModal() {
            const modal = document.getElementById('delete-chat-modal');
            if (modal) modal.style.display = 'none';
        }

        function submitDeleteChat() {
            if (roomToDelete) {
                const formData = new URLSearchParams();
                formData.append('action', 'delete');
                formData.append('room', roomToDelete);
                
                fetch('api.php', { method: 'POST', body: formData })
                    .then(() => {
                        // Also remove from virtualRooms if it was just drafted
                        virtualRooms = virtualRooms.filter(r => r !== roomToDelete);
                        if (currentRoom === roomToDelete) {
                            chatEl.textContent = "Eligere fabulationem e pluteo...";
                            currentRoom = '';
                            nuntiusInput.disabled = true;
                            sendBtn.disabled = true;
                            roomLabel.textContent = '';
                        }
                        loadChats();
                        closeDeleteModal();
                    });
            }
        }

        // Toggles State Management
        let currentLangMode = sessionStorage.getItem('lang_mode') || 'latin';
        let currentSearchMode = sessionStorage.getItem('search_mode') || 'off';

        function updateToggleUI() {
            const btnLang = document.getElementById('toggle-lang');
            const btnSearch = document.getElementById('toggle-search');
            const info = document.getElementById('toggles-info');

            if (currentLangMode === 'latin') {
                btnLang.textContent = "Lingua: Latina [L]";
                btnLang.classList.remove('toggle-active');
            } else {
                btnLang.textContent = "Lingua: Auto [A]";
                btnLang.classList.add('toggle-active');
            }

            if (currentSearchMode === 'off') {
                btnSearch.textContent = "Investigatio: OFF [-]";
                btnSearch.classList.remove('toggle-active');
            } else {
                btnSearch.textContent = "Investigatio: DDG [S]";
                btnSearch.classList.add('toggle-active');
            }

            info.textContent = `MODUS: ${currentLangMode.toUpperCase()} | SEARCH: ${currentSearchMode.toUpperCase()}`;
        }

        function toggleLanguage() {
            currentLangMode = currentLangMode === 'latin' ? 'auto' : 'latin';
            sessionStorage.setItem('lang_mode', currentLangMode);
            updateToggleUI();
        }

        function toggleSearch() {
            currentSearchMode = currentSearchMode === 'off' ? 'on' : 'off';
            sessionStorage.setItem('search_mode', currentSearchMode);
            updateToggleUI();
        }

        // Configuration Actions
        function openConfigModal() {
            document.getElementById('config-modal').style.display = 'flex';
            document.getElementById('config-alert').textContent = '';
            document.getElementById('config-new-name').value = '';
            document.getElementById('config-old-pass').value = '';
            document.getElementById('config-new-pass').value = '';
        }

        function closeConfigModal() {
            document.getElementById('config-modal').style.display = 'none';
            applyOptionsToDOM(); // Revert preview if not saved
        }

        function configAlert(msg, isError = false) {
            const el = document.getElementById('config-alert');
            el.style.color = isError ? '#ff3333' : '#00ff00';
            el.textContent = msg;
            setTimeout(() => { el.textContent = ''; }, 4000);
        }

        function renameUser() {
            const newName = document.getElementById('config-new-name').value.trim();
            if (!newName) return;
            const formData = new URLSearchParams();
            formData.append('action', 'renominare_usorem');
            formData.append('novum_nomen', newName);

            fetch('api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        configAlert("Nomen mutatum est. Reficiens... (Reloading)");
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        configAlert(data.message, true);
                    }
                });
        }

        function changePassword() {
            const oldPass = document.getElementById('config-old-pass').value;
            const newPass = document.getElementById('config-new-pass').value;
            if (!oldPass || !newPass) return;
            const formData = new URLSearchParams();
            formData.append('action', 'mutare_tessaram');
            formData.append('vetus_pass', oldPass);
            formData.append('nova_pass', newPass);

            fetch('api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        configAlert("Tessella mutata est (Password changed).");
                        document.getElementById('config-old-pass').value = '';
                        document.getElementById('config-new-pass').value = '';
                    } else {
                        configAlert(data.message, true);
                    }
                });
        }

        function deleteAllChats() {
            if (confirm("Visne vere delere omnes fabulationes? (Are you sure you want to delete all chats?)")) {
                const formData = new URLSearchParams();
                formData.append('action', 'delere_omnes_fabulationes');
                fetch('api.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'ok') {
                            configAlert("Omnes fabulationes deletae sunt. Reficiens...");
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            configAlert(data.message, true);
                        }
                    });
            }
        }

        function deleteAccount() {
            if (confirm("MONITUM! (WARNING!)\nVisne vere delere rationem et omnia data tua? Haec actio non potest revocari!\n(Are you sure you want to delete your account and all data? This cannot be undone!)")) {
                const formData = new URLSearchParams();
                formData.append('action', 'delere_rationem');
                fetch('api.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'ok') {
                            location.href = 'index.php';
                        } else {
                            configAlert(data.message, true);
                        }
                    });
            }
        }

        // --- Levels & Visual Options System ---

        const THEMES = [
            { name: "Viridis (Green)", colors: { main: '#00FF00', bg: '#050505', cont: '#000', dim: '#008800', dark: '#003300', hov: '#002200' } },
            { name: "Electinum (Amber)", colors: { main: '#FFB000', bg: '#100800', cont: '#000', dim: '#885500', dark: '#331100', hov: '#221100' } },
            { name: "Cyanus (Cyan)", colors: { main: '#00FFFF', bg: '#000810', cont: '#000', dim: '#008888', dark: '#003333', hov: '#002222' } },
            { name: "Cruor (Blood Red)", colors: { main: '#FF0000', bg: '#100000', cont: '#000', dim: '#880000', dark: '#330000', hov: '#220000' } },
            { name: "Matrix", colors: { main: '#00FF41', bg: '#000000', cont: '#001100', dim: '#008F11', dark: '#003B00', hov: '#002200' } },
            { name: "Purpura (Purple)", colors: { main: '#FF00FF', bg: '#080010', cont: '#000', dim: '#880088', dark: '#330033', hov: '#220022' } },
            { name: "Aureus (Gold)", colors: { main: '#FFD700', bg: '#101000', cont: '#000', dim: '#886600', dark: '#332200', hov: '#221100' } },
            { name: "Nix (White/Ice)", colors: { main: '#E0E0FF', bg: '#050510', cont: '#000', dim: '#8888AA', dark: '#222233', hov: '#111122' } },
            { name: "Neon (Vaporwave)", colors: { main: '#00FFFF', bg: '#2B00FF', cont: '#000000', dim: '#FF00FF', dark: '#880088', hov: '#110033' } },
            { name: "Cinereus (Ash)", colors: { main: '#AAAAAA', bg: '#111111', cont: '#000', dim: '#555555', dark: '#222222', hov: '#111111' } },
            { name: "Infernus (Inferno)", colors: { main: '#FF4500', bg: '#1A0000', cont: '#000', dim: '#AA2200', dark: '#440000', hov: '#220000' } },
            { name: "Abyssus (Deep Blue)", colors: { main: '#4169E1', bg: '#00001A', cont: '#000', dim: '#2233AA', dark: '#000044', hov: '#000022' } },
            { name: "Tenebrae (Pitch Black)", colors: { main: '#333333', bg: '#000', cont: '#000', dim: '#111111', dark: '#050505', hov: '#050505' } }
        ];

        let userState = { level: 1, messages: 0, options: { theme: 0, glitches: [] } };

        function loadUserState() {
            fetch('api.php?action=get_user_state')
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        userState.level = data.level || 1;
                        userState.messages = data.messages || 0;
                        if (data.options && typeof data.options.theme !== 'undefined') {
                            userState.options = data.options;
                        }
                        updateOptionsUI();
                        applyOptionsToDOM();
                    }
                });
        }

        function updateOptionsUI() {
            const themeSelect = document.getElementById('config-theme');
            themeSelect.innerHTML = '';
            THEMES.forEach((t, i) => {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = t.name;
                themeSelect.appendChild(opt);
            });
            themeSelect.value = userState.options.theme || 0;

            document.getElementById('glitch-10').parentElement.style.display = 'block';
            document.getElementById('glitch-11').parentElement.style.display = 'block';
            document.getElementById('glitch-12').parentElement.style.display = 'block';
            document.getElementById('glitch-13').parentElement.style.display = 'block';

            const g = userState.options.glitches || [];
            document.getElementById('glitch-10').checked = g.includes(10);
            document.getElementById('glitch-11').checked = g.includes(11);
            document.getElementById('glitch-12').checked = g.includes(12);
            document.getElementById('glitch-13').checked = g.includes(13);
            document.getElementById('glitch-20').checked = g.includes(20);
            document.getElementById('glitch-21').checked = g.includes(21);
            document.getElementById('glitch-22').checked = g.includes(22);
            document.getElementById('glitch-23').checked = g.includes(23);
            document.getElementById('glitch-24').checked = g.includes(24);
            document.getElementById('glitch-25').checked = g.includes(25);
            document.getElementById('glitch-26').checked = g.includes(26);
            document.getElementById('glitch-27').checked = g.includes(27);
            document.getElementById('glitch-28').checked = g.includes(28);

            if(document.getElementById('glitch-31')) document.getElementById('glitch-31').checked = g.includes(31);
            if(document.getElementById('glitch-32')) document.getElementById('glitch-32').checked = g.includes(32);
        }

        function applyTheme(themeIndex) {
            const t = THEMES[themeIndex] || THEMES[0];
            const root = document.documentElement;
            root.style.setProperty('--main-color', t.colors.main);
            root.style.setProperty('--bg-color', t.colors.bg);
            root.style.setProperty('--container-bg', t.colors.cont);
            root.style.setProperty('--dim-color', t.colors.dim);
            root.style.setProperty('--dark-color', t.colors.dark);
            root.style.setProperty('--hover-color', t.colors.hov);
        }

        function previewTheme() {
            applyTheme(parseInt(document.getElementById('config-theme').value));
        }

        function previewGlitches() {
            document.body.classList.toggle('glitch-shake', document.getElementById('glitch-10').checked);
            document.body.classList.toggle('glitch-chromatic', document.getElementById('glitch-11').checked);
            document.body.classList.toggle('glitch-borders', document.getElementById('glitch-12').checked);
            document.body.classList.toggle('glitch-noise', document.getElementById('glitch-13').checked);
            document.body.classList.toggle('glitch-scanlines', document.getElementById('glitch-20').checked);
            document.body.classList.toggle('glitch-vignette', document.getElementById('glitch-21').checked);
            document.body.classList.toggle('glitch-matrix', document.getElementById('glitch-22').checked);
            document.body.classList.toggle('glitch-fog', document.getElementById('glitch-23').checked);
            document.body.classList.toggle('glitch-blood', document.getElementById('glitch-24').checked);
            document.body.classList.toggle('glitch-stars', document.getElementById('glitch-25').checked);
            document.body.classList.toggle('glitch-eyes', document.getElementById('glitch-26').checked);
            document.body.classList.toggle('glitch-web', document.getElementById('glitch-27').checked);
            document.body.classList.toggle('glitch-pulse', document.getElementById('glitch-28').checked);
            document.body.classList.toggle('show-pacman', document.getElementById('glitch-31') && document.getElementById('glitch-31').checked);
            document.body.classList.toggle('show-mk2', document.getElementById('glitch-32') && document.getElementById('glitch-32').checked);
            document.body.classList.toggle('mute-sounds', document.getElementById('glitch-33') && document.getElementById('glitch-33').checked);
            document.body.classList.toggle('play-melody', document.getElementById('glitch-34') && document.getElementById('glitch-34').checked);
            document.body.classList.toggle('mech-clicks', document.getElementById('glitch-35') && document.getElementById('glitch-35').checked);
            document.body.classList.toggle('ambient-wind', document.getElementById('glitch-36') && document.getElementById('glitch-36').checked);
            document.body.classList.toggle('hum-sound', document.getElementById('glitch-37') && document.getElementById('glitch-37').checked);
        }

        function applyOptionsToDOM() {
            applyTheme(userState.options.theme || 0);
            const g = userState.options.glitches || [];
            document.body.classList.toggle('glitch-shake', g.includes(10));
            document.body.classList.toggle('glitch-chromatic', g.includes(11));
            document.body.classList.toggle('glitch-borders', g.includes(12));
            document.body.classList.toggle('glitch-noise', g.includes(13));
            document.body.classList.toggle('glitch-scanlines', g.includes(20));
            document.body.classList.toggle('glitch-vignette', g.includes(21));
            document.body.classList.toggle('glitch-matrix', g.includes(22));
            document.body.classList.toggle('glitch-fog', g.includes(23));
            document.body.classList.toggle('glitch-blood', g.includes(24));
            document.body.classList.toggle('glitch-stars', g.includes(25));
            document.body.classList.toggle('glitch-eyes', g.includes(26));
            document.body.classList.toggle('glitch-web', g.includes(27));
            document.body.classList.toggle('glitch-pulse', g.includes(28));
            document.body.classList.toggle('show-pacman', g.includes(31));
            document.body.classList.toggle('show-mk2', g.includes(32));
            document.body.classList.toggle('mute-sounds', g.includes(33));
            document.body.classList.toggle('play-melody', g.includes(34));
            document.body.classList.toggle('mech-clicks', g.includes(35));
            document.body.classList.toggle('ambient-wind', g.includes(36));
            document.body.classList.toggle('hum-sound', g.includes(37));
        }

        function saveVisualOptions() {
            const theme = parseInt(document.getElementById('config-theme').value);
            const glitches = [];
            if (document.getElementById('glitch-10').checked) glitches.push(10);
            if (document.getElementById('glitch-11').checked) glitches.push(11);
            if (document.getElementById('glitch-12').checked) glitches.push(12);
            if (document.getElementById('glitch-13').checked) glitches.push(13);
            if (document.getElementById('glitch-20').checked) glitches.push(20);
            if (document.getElementById('glitch-21').checked) glitches.push(21);
            if (document.getElementById('glitch-22').checked) glitches.push(22);
            if (document.getElementById('glitch-23').checked) glitches.push(23);
            if (document.getElementById('glitch-24').checked) glitches.push(24);
            if (document.getElementById('glitch-25').checked) glitches.push(25);
            if (document.getElementById('glitch-26').checked) glitches.push(26);
            if (document.getElementById('glitch-27').checked) glitches.push(27);
            if (document.getElementById('glitch-28').checked) glitches.push(28);
            if (document.getElementById('glitch-31') && document.getElementById('glitch-31').checked) glitches.push(31);
            if (document.getElementById('glitch-32') && document.getElementById('glitch-32').checked) glitches.push(32);
            if (document.getElementById('glitch-33') && document.getElementById('glitch-33').checked) glitches.push(33);
            if (document.getElementById('glitch-34') && document.getElementById('glitch-34').checked) glitches.push(34);
            if (document.getElementById('glitch-35') && document.getElementById('glitch-35').checked) glitches.push(35);
            if (document.getElementById('glitch-36') && document.getElementById('glitch-36').checked) glitches.push(36);
            if (document.getElementById('glitch-37') && document.getElementById('glitch-37').checked) glitches.push(37);

            userState.options = { theme, glitches };

            const formData = new URLSearchParams();
            formData.append('action', 'save_options');
            formData.append('options', JSON.stringify(userState.options));

            fetch('api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        configAlert("Optiones servatae sunt. (Options saved)");
                        applyOptionsToDOM();
                    } else {
                        configAlert(data.message, true);
                    }
                });
        }

        // Initial UI Update
        updateToggleUI();
        loadChats(true, true);
        loadUserState();
        
        // Matrix Rain Implementation
        const canvas = document.getElementById('matrixCanvas');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        const alphabet = 'АァカサタナハマヤャラワガザダバパイィキシチニヒミリヰギジヂビピウゥクスツヌフムユュルグズブヅプエェケセテネヘメレゲゼデベペオォコソトノホモヨョロゴゾドボポヴッンABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        const fontSize = 16;
        const columns = Math.floor(canvas.width / fontSize);
        const drops = [];
        for(let x = 0; x < columns; x++) drops[x] = 1;

        function drawMatrix() {
            if (!document.body.classList.contains('glitch-matrix')) return;
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            const color = getComputedStyle(document.documentElement).getPropertyValue('--main-color').trim() || '#0F0';
            ctx.fillStyle = color;
            ctx.font = fontSize + 'px monospace';
            for(let i = 0; i < drops.length; i++) {
                const text = alphabet.charAt(Math.floor(Math.random() * alphabet.length));
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);
                if(drops[i] * fontSize > canvas.height && Math.random() > 0.975) drops[i] = 0;
                drops[i]++;
            }
        }
        setInterval(drawMatrix, 33);
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            advCanvas.width = window.innerWidth;
            advCanvas.height = window.innerHeight;
        });

        // Advanced Effects Canvas (Blood, Stars, Eyes, Web)
        const advCanvas = document.getElementById('advCanvas');
        const advCtx = advCanvas.getContext('2d');
        advCanvas.width = window.innerWidth;
        advCanvas.height = window.innerHeight;

        let mouseX = 0; let mouseY = 0;
        window.addEventListener('mousemove', e => { mouseX = e.clientX; mouseY = e.clientY; });

        const pWeb = [];
        for(let i=0; i<80; i++) pWeb.push({x: Math.random()*window.innerWidth, y: Math.random()*window.innerHeight, vx: (Math.random()-0.5)*1.5, vy: (Math.random()-0.5)*1.5});

        const pStars = [];
        for(let i=0; i<200; i++) pStars.push({x: (Math.random()-0.5)*window.innerWidth*2, y: (Math.random()-0.5)*window.innerHeight*2, z: Math.random()*2000});

        const pEyes = [];
        function spawnEye() {
            if (pEyes.length < 5) pEyes.push({x: Math.random()*advCanvas.width, y: Math.random()*advCanvas.height, life: 0, maxLife: 100 + Math.random()*100});
            setTimeout(spawnEye, 1000 + Math.random()*3000);
        }
        spawnEye();

        const pBlood = [];
        for(let i=0; i<30; i++) pBlood.push({x: Math.random()*window.innerWidth, y: -Math.random()*window.innerHeight, speed: 1 + Math.random()*3, radius: 2 + Math.random()*4});

        function drawAdv() {
            requestAnimationFrame(drawAdv);
            const w = advCanvas.width; const h = advCanvas.height;
            const bBody = document.body;
            
            const isWeb = bBody.classList.contains('glitch-web');
            const isStars = bBody.classList.contains('glitch-stars');
            const isEyes = bBody.classList.contains('glitch-eyes');
            const isBlood = bBody.classList.contains('glitch-blood');
            
            if (!isWeb && !isStars && !isEyes && !isBlood) {
                advCtx.clearRect(0,0,w,h);
                return;
            }

            const mainC = getComputedStyle(document.documentElement).getPropertyValue('--main-color').trim() || '#0F0';
            
            if(isStars) {
                advCtx.fillStyle = 'rgba(0,0,0,0.3)';
                advCtx.fillRect(0,0,w,h);
            } else {
                advCtx.clearRect(0,0,w,h);
            }

            if (isStars) {
                advCtx.fillStyle = mainC;
                pStars.forEach(s => {
                    s.z -= 5;
                    if (s.z <= 0) { s.z = 2000; s.x = (Math.random()-0.5)*w*2; s.y = (Math.random()-0.5)*h*2; }
                    const px = (s.x / s.z) * 1000 + w/2;
                    const py = (s.y / s.z) * 1000 + h/2;
                    const size = (1 - s.z/2000) * 3;
                    advCtx.beginPath(); advCtx.arc(px,py,size,0,Math.PI*2); advCtx.fill();
                });
            }

            if (isBlood) {
                advCtx.fillStyle = '#8A0303';
                pBlood.forEach(b => {
                    b.y += b.speed;
                    if (b.y > h + 50) { b.y = -50; b.x = Math.random()*w; b.speed = 1 + Math.random()*2; }
                    
                    // Visceral drop shape
                    advCtx.beginPath();
                    advCtx.arc(b.x, b.y, b.radius, 0, Math.PI);
                    advCtx.lineTo(b.x + b.radius * 0.5, b.y - b.speed * 5);
                    advCtx.lineTo(b.x - b.radius * 0.5, b.y - b.speed * 5);
                    advCtx.fill();

                    // Trail / Smear effect
                    advCtx.globalAlpha = 0.3;
                    advCtx.beginPath();
                    advCtx.moveTo(b.x - b.radius*0.7, b.y);
                    advCtx.lineTo(b.x + b.radius*0.7, b.y);
                    advCtx.lineTo(b.x, b.y - b.speed * 15 - Math.random()*20);
                    advCtx.fill();
                    advCtx.globalAlpha = 1.0;
                });
            }

            if (isEyes) {
                pEyes.forEach((e, i) => {
                    e.life++;
                    if(e.life > e.maxLife) { pEyes.splice(i,1); return; }
                    const alpha = Math.sin((e.life/e.maxLife)*Math.PI) * 0.8;
                    advCtx.fillStyle = mainC;
                    advCtx.globalAlpha = alpha;
                    advCtx.beginPath(); advCtx.ellipse(e.x - 15, e.y, 8, 4, 0, 0, Math.PI*2); advCtx.fill();
                    advCtx.beginPath(); advCtx.ellipse(e.x + 15, e.y, 8, 4, 0, 0, Math.PI*2); advCtx.fill();
                    advCtx.fillStyle = '#000';
                    advCtx.beginPath(); advCtx.arc(e.x - 15, e.y, 2, 0, Math.PI*2); advCtx.fill();
                    advCtx.beginPath(); advCtx.arc(e.x + 15, e.y, 2, 0, Math.PI*2); advCtx.fill();
                    advCtx.globalAlpha = 1.0;
                });
            }

            if (isWeb) {
                advCtx.fillStyle = mainC;
                advCtx.strokeStyle = mainC;
                pWeb.forEach(p => {
                    p.x += p.vx; p.y += p.vy;
                    if(p.x < 0 || p.x > w) p.vx *= -1;
                    if(p.y < 0 || p.y > h) p.vy *= -1;
                    advCtx.beginPath(); advCtx.arc(p.x, p.y, 1.5, 0, Math.PI*2); advCtx.fill();
                });
                for(let i=0; i<pWeb.length; i++){
                    for(let j=i+1; j<pWeb.length; j++){
                        const dx = pWeb[i].x - pWeb[j].x;
                        const dy = pWeb[i].y - pWeb[j].y;
                        const dist = Math.sqrt(dx*dx + dy*dy);
                        if(dist < 100) {
                            advCtx.globalAlpha = 1 - dist/100;
                            advCtx.beginPath(); advCtx.moveTo(pWeb[i].x, pWeb[i].y); advCtx.lineTo(pWeb[j].x, pWeb[j].y); advCtx.stroke();
                        }
                    }
                    const mdX = pWeb[i].x - mouseX;
                    const mdY = pWeb[i].y - mouseY;
                    const mDist = Math.sqrt(mdX*mdX + mdY*mdY);
                    if(mDist < 150) {
                        advCtx.globalAlpha = 1 - mDist/150;
                        advCtx.beginPath(); advCtx.moveTo(pWeb[i].x, pWeb[i].y); advCtx.lineTo(mouseX, mouseY); advCtx.stroke();
                    }
                }
                advCtx.globalAlpha = 1.0;
            }
        }
        drawAdv();

        chatForm.onsubmit = function(e) {
            e.preventDefault();
            const msg = nuntiusInput.value.trim();
            if (!msg || !currentRoom) return;

            if (typeof playHDDSound === 'function') playHDDSound();

            nuntiusInput.value = '';
            nuntiusInput.disabled = true;
            sendBtn.disabled = true;

            if (chatEl.textContent === 'Nihil scriptum est...') {
                chatEl.innerHTML = '';
            }

            const userMsg = document.createElement('div');
            userMsg.className = 'msg-user';
            userMsg.innerHTML = DOMPurify.sanitize(marked.parse(`**Tute:** ${msg}`));
            renderMathInElement(userMsg, {
                delimiters: [
                    {left: '$$', right: '$$', display: true},
                    {left: '\\[', right: '\\]', display: true},
                    {left: '$', right: '$', display: false},
                    {left: '\\(', right: '\\)', display: false}
                ], throwOnError: false
            });
            chatEl.appendChild(userMsg);

            let oraclePrefix = document.createElement('div');
            oraclePrefix.className = 'msg-oracle';
            oraclePrefix.innerHTML = `<strong>Oraculum: </strong>`;
            chatEl.appendChild(oraclePrefix);

            chatEl.scrollTop = chatEl.scrollHeight;

            const formData = new URLSearchParams();
            formData.append('action', 'send');
            formData.append('room', currentRoom);
            formData.append('nuntius', msg);
            formData.append('lingua', currentLangMode);
            formData.append('search', currentSearchMode);

            let reasoningSpan = null;
            let normalTextSpan = document.createElement('span');
            oraclePrefix.appendChild(normalTextSpan);

            window.streamingState = null;
            fetch('api.php', {
                method: 'POST',
                body: formData,
            }).then(response => {
                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let sseBuffer = '';

                function read() {
                    reader.read().then(({ done, value }) => {
                        if (done) {
                            nuntiusInput.disabled = false;
                            sendBtn.disabled = false;
                            nuntiusInput.focus();
                            loadChats(); // refresh list in case it was a new chat
                            return;
                        }
                        sseBuffer += decoder.decode(value, {stream: true});
                        const lines = sseBuffer.split('\n');
                        sseBuffer = lines.pop(); // Keep the last incomplete chunk in buffer
                        
                        for (let line of lines) {
                            if (line.startsWith('data: ')) {
                                const dataStr = line.substring(6).trim();
                                if (dataStr === '[DONE]') {
                                    loadUserState(); // Update level after chat completes
                                    continue;
                                }
                                try {
                                    const dataNode = JSON.parse(dataStr);
                                    if (dataNode.event === 'renamed') {
                                        const oldRoom = currentRoom;
                                        currentRoom = dataNode.new_room;
                                        roomLabel.textContent = `[${currentRoom}]`;
                                        virtualRooms = virtualRooms.filter(r => r !== oldRoom);
                                        loadChats();
                                    } else if (dataNode.event === 'tool_call') {
                                        const toolSpan = document.createElement('div');
                                        toolSpan.className = 'tool-text';
                                        toolSpan.textContent = `[Instrumentum: ${dataNode.name}]`;
                                        chatEl.appendChild(toolSpan);
                                        
                                        oraclePrefix = document.createElement('div');
                                        oraclePrefix.innerHTML = `<strong>Oraculum: </strong>`;
                                        chatEl.appendChild(oraclePrefix);
                                        
                                        normalTextSpan = document.createElement('span');
                                        oraclePrefix.appendChild(normalTextSpan);
                                        chatEl.scrollTop = chatEl.scrollHeight;
                                     } else if (dataNode.choices && dataNode.choices[0].delta) {
                                         const delta = dataNode.choices[0].delta;
                                         if (delta.reasoning_content || delta.content) {
                                             if (!window.streamingState) {
                                                 window.streamingState = { reasoning: "", content: "", inThought: false, rid: 0 };
                                             }
                                             const s = window.streamingState;
                                             if (delta.reasoning_content) s.reasoning += delta.reasoning_content;
                                             if (delta.content) {
                                                 let c = delta.content;
                                                 if (c.includes("<thought>")) { s.inThought = true; c = c.replace("<thought>", ""); }
                                                 if (c.includes("</thought>")) { s.inThought = false; c = c.replace("</thought>", ""); }
                                                 if (s.inThought) s.reasoning += c; else s.content += c;
                                             }
                                             if (!s.rid) {
                                                 s.rid = requestAnimationFrame(() => {
                                                     if (!window.streamingState) return;
                                                     if (s.reasoning) {
                                                         if (!reasoningSpan) {
                                                             reasoningSpan = document.createElement('details');
                                                             reasoningSpan.className = 'reasoning-details';
                                                             reasoningSpan.innerHTML = '<summary>Cogitationes Oraculi...</summary><div class="reasoning-content"></div>';
                                                             chatEl.insertBefore(reasoningSpan, oraclePrefix);
                                                         }
                                                         const contentDiv = reasoningSpan.querySelector('.reasoning-content');
                                                         if (contentDiv) contentDiv.innerHTML = DOMPurify.sanitize(marked.parse(s.reasoning));
                                                     }
                                                     if (s.content) {
                                                         normalTextSpan.innerHTML = DOMPurify.sanitize(marked.parse(s.content));
                                                         renderMathInElement(normalTextSpan, {
                                                             delimiters: [
                                                                 {left: '$$', right: '$$', display: true}, {left: '\\[', right: '\\]', display: true},
                                                                 {left: '$', right: '$', display: false}, {left: '\\(', right: '\\)', display: false}
                                                             ], throwOnError: false
                                                         });
                                                     }
                                                     chatEl.scrollTop = chatEl.scrollHeight;
                                                     s.rid = 0;
                                                 });
                                             }
                                         }
                                     }
                                } catch(e) {}
                            }
                        }
                        read();
                    });
                }
                read();
            }).catch(err => {
                const errSpan = document.createElement('span');
                errSpan.textContent = "\nError: " + err;
                chatEl.appendChild(errSpan);
                nuntiusInput.disabled = false;
                sendBtn.disabled = false;
            });
        };
    </script>
    <!-- Web Audio API for retro interaction sounds -->
    <script>
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        function playClickSound() {
            if (audioCtx.state === 'suspended') { audioCtx.resume(); }
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            
            oscillator.type = 'square';
            oscillator.frequency.setValueAtTime(300, audioCtx.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(40, audioCtx.currentTime + 0.1);
            
            gainNode.gain.setValueAtTime(0.08, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.1);
            
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            
            oscillator.start();
            oscillator.stop(audioCtx.currentTime + 0.1);
        }

        let humOscillator = null;
        let humGain = null;

        function updateHumSound() {
            const isHumEnabled = document.body.classList.contains('hum-sound');
            const isMuted = document.body.classList.contains('mute-sounds');
            
            if (audioCtx.state === 'suspended' && isHumEnabled && !isMuted) { audioCtx.resume(); }
            
            if (humGain) {
                if (isHumEnabled && !isMuted) {
                    humGain.gain.setTargetAtTime(0.015, audioCtx.currentTime, 0.1);
                } else {
                    humGain.gain.setTargetAtTime(0, audioCtx.currentTime, 0.1);
                }
            }
        }

        function initHumSound() {
            if (humOscillator) return;
            if (audioCtx.state === 'suspended') { audioCtx.resume(); }
            
            humOscillator = audioCtx.createOscillator();
            humGain = audioCtx.createGain();
            let filter = audioCtx.createBiquadFilter();
            
            humOscillator.type = 'sawtooth';
            humOscillator.frequency.setValueAtTime(50, audioCtx.currentTime); // 50Hz mains hum
            
            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(120, audioCtx.currentTime);
            
            const isHumEnabled = document.body.classList.contains('hum-sound');
            const isMuted = document.body.classList.contains('mute-sounds');
            humGain.gain.setValueAtTime((isHumEnabled && !isMuted) ? 0.015 : 0, audioCtx.currentTime);
            
            humOscillator.connect(filter);
            filter.connect(humGain);
            humGain.connect(audioCtx.destination);
            
            humOscillator.start();
            
            const observer = new MutationObserver(updateHumSound);
            observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
        }

        function playHDDSound() {
            if (document.body.classList.contains('mute-sounds')) return;
            if (audioCtx.state === 'suspended') { audioCtx.resume(); }

            let t = audioCtx.currentTime;
            
            // Low spindle rumble
            let rumbleOsc = audioCtx.createOscillator();
            let rumbleGain = audioCtx.createGain();
            rumbleOsc.type = 'sawtooth';
            rumbleOsc.frequency.setValueAtTime(20, t);
            rumbleOsc.frequency.linearRampToValueAtTime(70, t + 1.5);
            rumbleOsc.frequency.linearRampToValueAtTime(75, t + 3.0);
            rumbleGain.gain.setValueAtTime(0.01, t);
            rumbleGain.gain.linearRampToValueAtTime(0.05, t + 1.5);
            rumbleGain.gain.setTargetAtTime(0, t + 2.5, 0.5);
            
            // High motor whine
            let whineOsc = audioCtx.createOscillator();
            let whineGain = audioCtx.createGain();
            whineOsc.type = 'triangle';
            whineOsc.frequency.setValueAtTime(80, t);
            whineOsc.frequency.exponentialRampToValueAtTime(1200, t + 1.5);
            whineOsc.frequency.linearRampToValueAtTime(1250, t + 3.0);
            whineGain.gain.setValueAtTime(0.001, t);
            whineGain.gain.linearRampToValueAtTime(0.02, t + 1.5);
            whineGain.gain.setTargetAtTime(0, t + 2.5, 0.5);
            
            let filter = audioCtx.createBiquadFilter();
            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(2000, t);
            
            rumbleOsc.connect(rumbleGain);
            whineOsc.connect(whineGain);
            rumbleGain.connect(filter);
            whineGain.connect(filter);
            filter.connect(audioCtx.destination);
            
            rumbleOsc.start(t);
            whineOsc.start(t);
            rumbleOsc.stop(t + 3.5);
            whineOsc.stop(t + 3.5);
            
            // Seeking clicks
            let seekInterval = setInterval(() => {
                if (document.body.classList.contains('mute-sounds')) {
                    clearInterval(seekInterval); return;
                }
                let cTime = audioCtx.currentTime;
                let clickOsc = audioCtx.createOscillator();
                let clickGain = audioCtx.createGain();
                clickOsc.type = 'square';
                clickOsc.frequency.setValueAtTime(600 + Math.random() * 600, cTime);
                clickGain.gain.setValueAtTime(0.015 + Math.random() * 0.015, cTime);
                clickGain.gain.exponentialRampToValueAtTime(0.001, cTime + 0.015);
                
                let clickFilter = audioCtx.createBiquadFilter();
                clickFilter.type = 'bandpass';
                clickFilter.frequency.setValueAtTime(1500, cTime);
                
                clickOsc.connect(clickGain);
                clickGain.connect(clickFilter);
                clickFilter.connect(audioCtx.destination);
                
                clickOsc.start(cTime);
                clickOsc.stop(cTime + 0.015);
            }, 35);
            
            setTimeout(() => clearInterval(seekInterval), 2500);
        }

        function playMechClickSound() {
            if (audioCtx.state === 'suspended') { audioCtx.resume(); }
            let t = audioCtx.currentTime;
            
            // "Thock" sound (low frequency)
            let osc = audioCtx.createOscillator();
            let gain = audioCtx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(150, t);
            osc.frequency.exponentialRampToValueAtTime(40, t + 0.05);
            gain.gain.setValueAtTime(0.3, t);
            gain.gain.exponentialRampToValueAtTime(0.001, t + 0.05);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(t);
            osc.stop(t + 0.05);

            // High frequency "clack"
            let osc2 = audioCtx.createOscillator();
            let gain2 = audioCtx.createGain();
            osc2.type = 'square';
            osc2.frequency.setValueAtTime(800, t);
            osc2.frequency.exponentialRampToValueAtTime(200, t + 0.02);
            gain2.gain.setValueAtTime(0.05, t);
            gain2.gain.exponentialRampToValueAtTime(0.001, t + 0.02);
            
            let filter = audioCtx.createBiquadFilter();
            filter.type = 'highpass';
            filter.frequency.setValueAtTime(1000, t);
            
            osc2.connect(gain2);
            gain2.connect(filter);
            filter.connect(audioCtx.destination);
            osc2.start(t);
            osc2.stop(t + 0.02);
        }

        let windBufferSrc = null;
        let windGain = null;
        let windFilter = null;
        function initWindSound() {
            if (windBufferSrc) return;
            if (audioCtx.state === 'suspended') { audioCtx.resume(); }
            
            let bufferSize = audioCtx.sampleRate * 2;
            let buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
            let data = buffer.getChannelData(0);
            for (let i = 0; i < bufferSize; i++) {
                data[i] = Math.random() * 2 - 1;
            }
            
            windBufferSrc = audioCtx.createBufferSource();
            windBufferSrc.buffer = buffer;
            windBufferSrc.loop = true;
            
            windFilter = audioCtx.createBiquadFilter();
            windFilter.type = 'bandpass';
            windFilter.frequency.setValueAtTime(400, audioCtx.currentTime);
            windFilter.Q.setValueAtTime(0.8, audioCtx.currentTime);
            
            let lfo = audioCtx.createOscillator();
            lfo.type = 'sine';
            lfo.frequency.setValueAtTime(0.15, audioCtx.currentTime);
            let lfoGain = audioCtx.createGain();
            lfoGain.gain.setValueAtTime(300, audioCtx.currentTime);
            lfo.connect(lfoGain);
            lfoGain.connect(windFilter.frequency);
            lfo.start();
            
            windGain = audioCtx.createGain();
            windGain.gain.setValueAtTime(0, audioCtx.currentTime);
            
            windBufferSrc.connect(windFilter);
            windFilter.connect(windGain);
            windGain.connect(audioCtx.destination);
            windBufferSrc.start();
            
            setInterval(() => {
                const isWindy = document.body.classList.contains('ambient-wind');
                const isMuted = document.body.classList.contains('mute-sounds');
                let targetGain = (isWindy && !isMuted) ? 0.06 : 0;
                windGain.gain.setTargetAtTime(targetGain, audioCtx.currentTime, 1.0);
            }, 1000);
        }

        let melodyInterval = null;
        const scale = [261.63, 293.66, 329.63, 349.23, 392.00, 440.00, 493.88, 523.25]; // C major
        let noteIndex = 0;
        const melodyPattern = [0, 2, 4, 7, 7, 4, 2, 0, 1, 3, 5, 7, 5, 3, 1, 0];
        
        function initMelody() {
            if (melodyInterval) return;
            if (audioCtx.state === 'suspended') { audioCtx.resume(); }
            melodyInterval = setInterval(() => {
                const playM = document.body.classList.contains('play-melody');
                const isMuted = document.body.classList.contains('mute-sounds');
                
                if (playM && !isMuted) {
                    let t = audioCtx.currentTime;
                    let freq = scale[melodyPattern[noteIndex]] || scale[0];
                    noteIndex = (noteIndex + 1) % melodyPattern.length;
                    
                    let osc = audioCtx.createOscillator();
                    let gain = audioCtx.createGain();
                    osc.type = 'square';
                    osc.frequency.setValueAtTime(freq / 2, t);
                    
                    gain.gain.setValueAtTime(0.015, t);
                    gain.gain.exponentialRampToValueAtTime(0.001, t + 0.15);
                    
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start(t);
                    osc.stop(t + 0.2);
                }
            }, 200);
        }

        document.addEventListener('click', function(e) {
            initHumSound();
            initWindSound();
            initMelody();
            if (document.body.classList.contains('mute-sounds')) return;
            if (e.target.closest('button') || 
                e.target.closest('input[type="submit"]') || 
                e.target.closest('input[type="checkbox"]') || 
                e.target.closest('.chat-item')) {
                if (document.body.classList.contains('mech-clicks')) {
                    playMechClickSound();
                } else {
                    playClickSound();
                }
            }
        });
    </script>
</body>
</html>
