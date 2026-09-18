<!-- Floating AI Assistant Button & Modal -->
<div id="aiAssistantContainer" class="fixed bottom-6 right-6 z-50 flex flex-col items-end print:hidden">
    <!-- Chat Drawer / Modal -->
    <div id="aiChatDrawer" class="hidden w-[92vw] sm:w-[400px] h-[520px] max-h-[85vh] bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden mb-3 animate-in fade-in slide-in-from-bottom-5 duration-200">
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-800 via-forest-900 to-forest-950 text-white px-4 py-3.5 flex items-center justify-between shadow-md">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-emerald-700/80 border border-emerald-400 flex items-center justify-center text-lg shadow-xs">
                    🤖
                </div>
                <div>
                    <div class="font-bold text-xs flex items-center gap-1.5 tracking-wide">
                        <span>ELEPHANT AI ASSISTANT</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    </div>
                    <div class="text-[10px] text-emerald-200/90 font-medium">Bantuan Cepat Kasir & Akuntansi</div>
                </div>
            </div>
            <button onclick="toggleAiDrawer()" class="p-1 text-slate-300 hover:text-white rounded-lg hover:bg-white/10 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Messages Container -->
        <div id="aiChatMessages" class="flex-1 p-4 overflow-y-auto space-y-3 bg-slate-50/70 text-xs">
            <!-- Greeting Message -->
            <div class="flex gap-2.5 items-start">
                <div class="w-6 h-6 rounded-full bg-emerald-800 text-white flex items-center justify-center text-xs shrink-0 mt-0.5 font-bold shadow-xs">
                    AI
                </div>
                <div class="bg-white p-3 rounded-2xl rounded-tl-none border border-slate-200/80 shadow-xs text-slate-800 max-w-[85%] leading-relaxed">
                    Halo <strong>{{ Auth::user()->name ?? 'Kasir' }}</strong>! 👋<br>
                    Ada yang bisa saya bantu terkait transaksi POS, tarik tunai, transfer, stok, atau laporan keuangan?
                </div>
            </div>

            <!-- Quick Questions Chips -->
            <div id="aiQuickChips" class="flex flex-wrap gap-1.5 pt-1">
                <button onclick="sendQuickPrompt('Bagaimana cara proses Tarik Tunai nasabah di kasir?')" class="text-[11px] bg-white border border-emerald-300 hover:bg-emerald-50 text-emerald-900 px-2.5 py-1 rounded-full shadow-2xs transition">
                    💵 Cara Tarik Tunai?
                </button>
                <button onclick="sendQuickPrompt('Bagaimana SOP rekap shift dan setor uang kasir saat tutup toko?')" class="text-[11px] bg-white border border-emerald-300 hover:bg-emerald-50 text-emerald-900 px-2.5 py-1 rounded-full shadow-2xs transition">
                    📝 Tutup Shift & Setor?
                </button>
                <button onclick="sendQuickPrompt('Bagaimana cara catat pengeluaran kas keluar operasional toko?')" class="text-[11px] bg-white border border-emerald-300 hover:bg-emerald-50 text-emerald-900 px-2.5 py-1 rounded-full shadow-2xs transition">
                    💸 Catat Kas Keluar?
                </button>
                <button onclick="sendQuickPrompt('Bagaimana cara retur penjualan barang cacat/rusak?')" class="text-[11px] bg-white border border-emerald-300 hover:bg-emerald-50 text-emerald-900 px-2.5 py-1 rounded-full shadow-2xs transition">
                    🔄 Cara Retur Barang?
                </button>
            </div>
        </div>

        <!-- Input Box -->
        <div class="p-3 bg-white border-t border-slate-200">
            <form id="aiChatForm" onsubmit="submitAiMessage(event)" class="flex gap-2 items-center">
                <input type="text" id="aiUserInput" placeholder="Ketik pertanyaan Anda di sini..." class="flex-1 text-xs px-3 py-2 bg-slate-100 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition" autocomplete="off" required>
                <button type="submit" id="aiBtnSend" class="bg-emerald-800 hover:bg-emerald-900 text-white p-2 rounded-xl transition shadow-xs disabled:opacity-50">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>
            <div class="text-[10px] text-slate-400 text-center mt-1.5 flex items-center justify-center gap-1">
                <span>Didukung oleh Google Gemini AI</span>
            </div>
        </div>
    </div>

    <!-- Floating Toggle Button -->
    <button onclick="toggleAiDrawer()" id="aiFloatingBtn" class="group bg-gradient-to-tr from-emerald-800 to-forest-900 hover:from-emerald-700 hover:to-forest-800 text-white p-3.5 rounded-full shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-200 flex items-center gap-2 border-2 border-emerald-400/40">
        <span class="text-xl">🤖</span>
        <span class="hidden sm:inline font-bold text-xs pr-1">Tanya AI</span>
    </button>
</div>

<script>
    let aiHistory = [];

    function toggleAiDrawer() {
        const drawer = document.getElementById('aiChatDrawer');
        if (drawer.classList.contains('hidden')) {
            drawer.classList.remove('hidden');
            setTimeout(() => document.getElementById('aiUserInput').focus(), 100);
        } else {
            drawer.classList.add('hidden');
        }
    }

    function sendQuickPrompt(promptText) {
        document.getElementById('aiUserInput').value = promptText;
        submitAiMessage(new Event('submit'));
    }

    async function submitAiMessage(e) {
        if (e) e.preventDefault();
        const input = document.getElementById('aiUserInput');
        const text = input.value.trim();
        if (!text) return;

        input.value = '';
        const container = document.getElementById('aiChatMessages');

        // Hide quick chips once chatting
        const chips = document.getElementById('aiQuickChips');
        if (chips) chips.style.display = 'none';

        // Append User Message
        appendMessage('user', text);

        // Append Loading Indicator
        const loadingId = 'aiLoading_' + Date.now();
        const loadingHtml = `
            <div id="${loadingId}" class="flex gap-2.5 items-start">
                <div class="w-6 h-6 rounded-full bg-emerald-800 text-white flex items-center justify-center text-xs shrink-0 font-bold shadow-xs">AI</div>
                <div class="bg-white p-3 rounded-2xl rounded-tl-none border border-slate-200 shadow-xs text-slate-500 text-xs flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-bounce"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-bounce" style="animation-delay: 0.15s"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-bounce" style="animation-delay: 0.3s"></span>
                    <span class="text-[11px] ml-1">Mengetik jawaban...</span>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', loadingHtml);
        container.scrollTop = container.scrollHeight;

        const btnSend = document.getElementById('aiBtnSend');
        btnSend.disabled = true;

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('{{ route("ai.ask") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    message: text,
                    history: aiHistory.slice(-6),
                })
            });

            const data = await res.json();
            document.getElementById(loadingId)?.remove();

            if (data.success && data.reply) {
                appendMessage('model', data.reply);
                aiHistory.push({ role: 'user', content: text });
                aiHistory.push({ role: 'model', content: data.reply });
            } else {
                appendMessage('model', '⚠️ ' + (data.message || 'Gagal memproses jawaban. Pastikan API key Google Gemini telah diatur.'));
            }
        } catch (err) {
            document.getElementById(loadingId)?.remove();
            appendMessage('model', '⚠️ Gagal terhubung ke server AI: ' + err.message);
        } finally {
            btnSend.disabled = false;
            container.scrollTop = container.scrollHeight;
        }
    }

    function appendMessage(sender, text) {
        const container = document.getElementById('aiChatMessages');
        let html = '';

        if (sender === 'user') {
            html = `
                <div class="flex justify-end">
                    <div class="bg-emerald-800 text-white p-3 rounded-2xl rounded-tr-none shadow-xs max-w-[85%] leading-relaxed text-xs">
                        ${escapeHtml(text)}
                    </div>
                </div>
            `;
        } else {
            // Render basic markdown formatting (bold, newlines, bullet points)
            const formatted = formatAiText(text);
            html = `
                <div class="flex gap-2.5 items-start">
                    <div class="w-6 h-6 rounded-full bg-emerald-800 text-white flex items-center justify-center text-xs shrink-0 mt-0.5 font-bold shadow-xs">AI</div>
                    <div class="bg-white p-3 rounded-2xl rounded-tl-none border border-slate-200/80 shadow-xs text-slate-800 max-w-[88%] leading-relaxed text-xs">
                        ${formatted}
                    </div>
                </div>
            `;
        }

        container.insertAdjacentHTML('beforeend', html);
        container.scrollTop = container.scrollHeight;
    }

    function escapeHtml(string) {
        return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function formatAiText(text) {
        let esc = escapeHtml(text);
        // Replace bold **text**
        esc = esc.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Replace newlines
        esc = esc.replace(/\n/g, '<br>');
        return esc;
    }
</script>
