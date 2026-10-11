<script setup>
defineProps({
    name: {
        type: String,
        required: true,
    },
    className: {
        type: String,
        default: 'w-5 h-5',
    },
});
</script>

<template>
    <svg
        v-if="name === 'instagram'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
    </svg>

    <svg
        v-else-if="name === 'youtube'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"></path>
        <polygon points="10 15 15 12 10 9 10 15"></polygon>
    </svg>

    <svg
        v-else-if="name === 'tiktok'"
        :class="className"
        viewBox="0 0 24 24"
        fill="currentColor"
    >
        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64c.29 0 .58.04.86.12V9.42a6.33 6.33 0 0 0-.86-.06 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.71a8.17 8.17 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.14z"/>
    </svg>

    <svg
        v-else-if="name === 'twitter' || name === 'x'"
        :class="className"
        viewBox="0 0 24 24"
        fill="currentColor"
    >
        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
    </svg>

    <svg
        v-else-if="name === 'whatsapp'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
    </svg>

    <svg
        v-else-if="name === 'linkedin'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
        <rect x="2" y="9" width="4" height="12"></rect>
        <circle cx="4" cy="4" r="2"></circle>
    </svg>

    <svg
        v-else-if="name === 'github'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path>
    </svg>

    <svg
        v-else-if="name === 'nfc'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
    </svg>

    <svg
        v-else-if="name === 'card'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
        <line x1="1" y1="10" x2="23" y2="10"></line>
    </svg>

    <svg
        v-else-if="name === 'share'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <circle cx="18" cy="5" r="3"></circle>
        <circle cx="6" cy="12" r="3"></circle>
        <circle cx="18" cy="19" r="3"></circle>
        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
    </svg>

    <svg
        v-else-if="name === 'qrcode'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <rect x="3" y="3" width="7" height="7"></rect>
        <rect x="14" y="3" width="7" height="7"></rect>
        <rect x="14" y="14" width="7" height="7"></rect>
        <rect x="3" y="14" width="7" height="7"></rect>
    </svg>

    <svg
        v-else-if="name === 'sparkles'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>
        <path d="M5 3v4"></path>
        <path d="M19 17v4"></path>
        <path d="M3 5h4"></path>
        <path d="M17 19h4"></path>
    </svg>

    <svg
        v-else-if="name === 'trash'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <polyline points="3 6 5 6 21 6"></polyline>
        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
    </svg>

    <svg
        v-else-if="name === 'eye'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
        <circle cx="12" cy="12" r="3"></circle>
    </svg>

    <svg
        v-else-if="name === 'check'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <polyline points="20 6 9 17 4 12"></polyline>
    </svg>

    <svg
        v-else-if="name === 'chevron-up'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <polyline points="18 15 12 9 6 15"></polyline>
    </svg>

    <svg
        v-else-if="name === 'chevron-down'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <polyline points="6 9 12 15 18 9"></polyline>
    </svg>

    <svg
        v-else-if="name === 'spotify'"
        :class="className"
        viewBox="0 0 24 24"
        fill="currentColor"
    >
        <path d="M12 2C6.477 2 2 6.477 2 12c0 5.524 4.477 10 10 10s10-4.476 10-10c0-5.523-4.477-10-10-10zm4.586 14.424a.627.627 0 0 1-.861.208c-2.358-1.441-5.326-1.767-8.823-.968a.626.626 0 1 1-.28-1.222c3.824-.874 7.106-.503 9.756 1.121a.627.627 0 0 1 .208.861zm1.224-2.721a.784.784 0 0 1-1.079.258c-2.7-1.66-6.816-2.14-10.009-1.171a.784.784 0 1 1-.453-1.5c3.649-1.107 8.2-.577 11.283 1.334a.783.783 0 0 1 .258 1.079zm.105-2.835C14.692 8.91 9.38 8.734 6.305 9.668a.94.94 0 1 1-.548-1.8c3.535-1.073 9.404-.866 13.238 1.41a.942.942 0 0 1-.309 1.74z"/>
    </svg>

    <svg
        v-else-if="name === 'threads'"
        :class="className"
        viewBox="0 0 24 24"
        fill="currentColor"
    >
        <path d="M12.186 24C5.467 24 0 18.533 0 11.814 0 5.094 5.467 0 12.186 0c6.643 0 11.884 5.234 11.884 11.884 0 .546-.443.989-.989.989-.546 0-.989-.443-.989-.989 0-5.568-4.338-9.906-9.906-9.906-5.632 0-10.208 4.576-10.208 10.208 0 5.632 4.576 10.208 10.208 10.208 4.298 0 7.857-2.677 9.243-6.495.188-.513.76-.78 1.274-.592.513.189.78.761.592 1.274-1.66 4.567-5.918 7.768-11.089 7.768z"/>
        <path d="M12.186 18.156c-3.393 0-6.156-2.763-6.156-6.156s2.763-6.156 6.156-6.156c3.328 0 5.962 2.634 5.962 5.962 0 .546-.443.989-.989.989-.546 0-.989-.443-.989-.989 0-2.235-1.738-3.984-3.984-3.984-2.304 0-4.178 1.874-4.178 4.178 0 2.304 1.874 4.178 4.178 4.178 1.764 0 3.287-1.123 3.864-2.738.187-.514.76-.782 1.273-.594.514.187.782.76.594 1.273-.787 2.203-2.868 3.737-5.277 3.737z"/>
    </svg>

    <svg
        v-else-if="name === 'behance'"
        :class="className"
        viewBox="0 0 24 24"
        fill="currentColor"
    >
        <path d="M22 7h-7v2h7V7zm1.726 10c-.442 1.297-2.029 3-4.726 3-3.149 0-5.5-2.28-5.5-5.5 0-3.38 2.45-5.5 5.5-5.5 3.399 0 5.309 2.479 5.095 5.5h-8.595c.084 1.815 1.488 3.3 3.5 3.3 1.542 0 2.656-.632 3.125-1.5l1.601.7zm-4.726-5.8c-1.637 0-2.776 1.082-2.951 2.6h5.801c-.1-1.37-1.077-2.6-2.85-2.6zM8.008 12.875C9.28 12.274 10 11.088 10 9.5c0-2.585-2.02-4.5-5.5-4.5H0v14h4.86c3.67 0 5.76-2.043 5.76-4.75 0-1.616-.62-2.875-2.612-3.375zM3 7.5h1.7c1.4 0 2.3.7 2.3 1.8s-.9 1.8-2.3 1.8H3V7.5zm2.1 9H3v-3.7h2.1c1.5 0 2.5.8 2.5 1.9 0 1.1-.9 1.8-2.5 1.8z"/>
    </svg>

    <svg
        v-else-if="name === 'dribbble'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <circle cx="12" cy="12" r="10"></circle>
        <path d="M19.13 5.09C15.22 9.14 10 10.44 2.25 10.94"></path>
        <path d="M21.75 12.84c-6.62-1.41-12.14 1-16.38 6.32"></path>
        <path d="M8.56 2.75c4.37 6 6 9.42 8 17.72"></path>
    </svg>

    <svg
        v-else-if="name === 'pinterest'"
        :class="className"
        viewBox="0 0 24 24"
        fill="currentColor"
    >
        <path d="M12 0C5.373 0 0 5.372 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738.098.119.112.224.083.345-.09.375-.291 1.199-.332 1.357-.053.224-.174.271-.4.164-1.49-.694-2.42-2.873-2.42-4.627 0-3.769 2.739-7.231 7.896-7.231 4.144 0 7.365 2.953 7.365 6.899 0 4.117-2.595 7.431-6.199 7.431-1.211 0-2.348-.63-2.738-1.374l-.746 2.846c-.27 1.039-1.002 2.341-1.492 3.136 1.12.346 2.304.535 3.534.535 6.627 0 12-5.373 12-12 0-6.628-5.373-12-12-12z"/>
    </svg>

    <svg
        v-else-if="name === 'facebook'"
        :class="className"
        viewBox="0 0 24 24"
        fill="currentColor"
    >
        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
    </svg>

    <svg
        v-else-if="name === 'keychain'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <circle cx="12" cy="6" r="3.5"></circle>
        <path d="M12 9.5v3"></path>
        <rect x="7" y="12.5" width="10" height="9" rx="3"></rect>
        <circle cx="12" cy="17" r="1.5"></circle>
    </svg>

    <svg
        v-else-if="name === 'search'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    </svg>

    <svg
        v-else-if="name === 'plus'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>

    <svg
        v-else-if="name === 'camera'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
        <circle cx="12" cy="13" r="4"></circle>
    </svg>

    <svg
        v-else-if="name === 'upload'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
        <polyline points="17 8 12 3 7 8"></polyline>
        <line x1="12" y1="3" x2="12" y2="15"></line>
    </svg>

    <svg
        v-else-if="name === 'star'"
        :class="className"
        viewBox="0 0 24 24"
        fill="currentColor"
    >
        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
    </svg>

    <svg
        v-else-if="name === 'mail'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
        <polyline points="22,6 12,13 2,6"></polyline>
    </svg>

    <svg
        v-else-if="name === 'phone'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
    </svg>

    <svg
        v-else-if="name === 'download'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
        <polyline points="7 10 12 15 17 10"></polyline>
        <line x1="12" y1="15" x2="12" y2="3"></line>
    </svg>

    <svg
        v-else-if="name === 'quote'"
        :class="className"
        viewBox="0 0 24 24"
        fill="currentColor"
    >
        <path d="M3 21c3 0 7-1 7-8V5c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h4c0 3.5-2 5.5-5 6v2zm13 0c3 0 7-1 7-8V5c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h4c0 3.5-2 5.5-5 6v2z"></path>
    </svg>

    <svg
        v-else-if="name === 'heart'"
        :class="className"
        viewBox="0 0 24 24"
        fill="currentColor"
    >
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
    </svg>

    <svg
        v-else-if="name === 'briefcase'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
    </svg>

    <svg
        v-else-if="name === 'pencil'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
    </svg>

    <svg
        v-else-if="name === 'external-link'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
        <polyline points="15 3 21 3 21 9"></polyline>
        <line x1="10" y1="14" x2="21" y2="3"></line>
    </svg>

    <svg
        v-else-if="name === 'globe'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="2" y1="12" x2="22" y2="12"></line>
        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
    </svg>

    <svg
        v-else-if="name === 'help' || name === 'info'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <circle cx="12" cy="12" r="10"></circle>
        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
    </svg>

    <svg
        v-else-if="name === 'book-open'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
    </svg>

    <svg
        v-else-if="name === 'smartphone' || name === 'mobile'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
        <line x1="12" y1="18" x2="12.01" y2="18"></line>
    </svg>

    <svg
        v-else-if="name === 'laptop' || name === 'desktop'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M20 16V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9m16 0H4m16 0 1.28 2.55a1 1 0 0 1-.9 1.45H3.62a1 1 0 0 1-.9-1.45L4 16"></path>
    </svg>

    <svg
        v-else-if="name === 'trophy' || name === 'award'"
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
        <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
        <path d="M4 22h16"></path>
        <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
        <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
        <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path>
    </svg>

    <!-- Default fallback: Globe / Link -->
    <svg
        v-else
        :class="className"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
    </svg>
</template>
