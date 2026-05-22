// Necromancer - Common Module (DOM selectors and global state)

let currentRoom = '';
let virtualRooms = [];
let userState = { level: 1, messages: 0, options: { theme: 0, glitches: [], volume: 80 } };
let currentLangMode = sessionStorage.getItem('lang_mode') || 'latin';
let currentSearchMode = sessionStorage.getItem('search_mode') || 'off';

const chatEl = document.getElementById("chat");
const chatListEl = document.getElementById("chat-list");
const chatForm = document.getElementById("chat-form");
const nuntiusInput = document.getElementById("nuntius");
const sendBtn = document.getElementById("send-btn");
const roomLabel = document.getElementById("current-room-label");
