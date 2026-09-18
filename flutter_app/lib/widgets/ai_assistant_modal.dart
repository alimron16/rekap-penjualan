import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../utils/theme_config.dart';

class AiAssistantModal extends StatefulWidget {
  const AiAssistantModal({super.key});

  static void show(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const AiAssistantModal(),
    );
  }

  @override
  State<AiAssistantModal> createState() => _AiAssistantModalState();
}

class _AiAssistantModalState extends State<AiAssistantModal>
    with SingleTickerProviderStateMixin {
  final TextEditingController _inputController = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  final List<Map<String, dynamic>> _messages = [
    {
      'role': 'model',
      'text': 'Halo! Saya Elephant AI Assistant.\nAda yang bisa saya bantu terkait transaksi kasir, tarik tunai, setor shift, stok, atau kendala lainnya?',
      'isError': false,
    }
  ];

  bool _isLoading = false;
  String? _lastUserMessage;
  late AnimationController _dotController;

  final List<String> _quickPrompts = [
    'Cara Tarik Tunai di Kasir?',
    'Cara Tutup Shift & Setor Kasir?',
    'Cara Catat Pengeluaran Kas Keluar?',
    'Cara Retur Penjualan Barang Rusak?',
  ];

  @override
  void initState() {
    super.initState();
    _dotController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..repeat();
  }

  @override
  void dispose() {
    _inputController.dispose();
    _scrollController.dispose();
    _dotController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  List<Map<String, String>> _buildHistory() {
    final allMsgs = _messages
        .where((m) => !((m['isError'] as bool? ?? false)))
        .where((m) => m['role'] == 'user' || m['role'] == 'model')
        .toList();

    if (allMsgs.isNotEmpty && allMsgs.last['role'] == 'user') {
      allMsgs.removeLast();
    }

    final recent = allMsgs.length > 8 ? allMsgs.sublist(allMsgs.length - 8) : allMsgs;
    return recent
        .map((m) => {'role': m['role'] as String, 'content': m['text'] as String})
        .toList();
  }

  Future<void> _sendMessage(String query) async {
    final text = query.trim();
    if (text.isEmpty || _isLoading) return;

    _inputController.clear();
    _lastUserMessage = text;

    setState(() {
      _messages.add({'role': 'user', 'text': text, 'isError': false});
      _isLoading = true;
    });
    _scrollToBottom();

    final history = _buildHistory();

    try {
      final res = await ApiService.askAi(message: text, history: history);
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        if (res['success'] == true && res['reply'] != null) {
          _messages.add({'role': 'model', 'text': res['reply'].toString(), 'isError': false});
        } else {
          final errMsg = res['message']?.toString() ?? 'Gagal memproses jawaban. Coba lagi sebentar.';
          _messages.add({'role': 'model', 'text': 'Error: $errMsg', 'isError': true});
        }
      });
      _scrollToBottom();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _messages.add({'role': 'model', 'text': 'Error: Koneksi ke server AI gagal. Periksa jaringan Anda.', 'isError': true});
      });
      _scrollToBottom();
    }
  }

  Future<void> _retryLastMessage() async {
    if (_lastUserMessage == null || _isLoading) return;
    if (_messages.isNotEmpty && (_messages.last['isError'] as bool? ?? false)) {
      setState(() => _messages.removeLast());
    }
    if (_messages.isNotEmpty && _messages.last['role'] == 'user') {
      final msg = _messages.last['text'] as String;
      setState(() => _messages.removeLast());
      await _sendMessage(msg);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    return Container(
      height: MediaQuery.of(context).size.height * 0.87,
      padding: EdgeInsets.only(bottom: bottomInset),
      decoration: const BoxDecoration(
        color: Color(0xFFF8FAFC),
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        children: [
          Container(
            margin: const EdgeInsets.only(top: 10, bottom: 4),
            width: 36,
            height: 4,
            decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: const BoxDecoration(color: ThemeConfig.primary),
            child: Row(
              children: [
                Container(
                  width: 38, height: 38,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.15),
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
                  ),
                  child: const Icon(Icons.smart_toy_rounded, color: Colors.white, size: 20),
                ),
                const SizedBox(width: 12),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Elephant AI Assistant', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 14)),
                      Text('Powered by Google Gemini', style: TextStyle(color: Colors.white70, fontSize: 10)),
                    ],
                  ),
                ),
                IconButton(icon: const Icon(Icons.close_rounded, color: Colors.white70, size: 20), onPressed: () => Navigator.pop(context)),
              ],
            ),
          ),
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.fromLTRB(14, 14, 14, 6),
              itemCount: _messages.length + (_isLoading ? 1 : 0),
              itemBuilder: (context, index) {
                if (index == _messages.length && _isLoading) return _buildTypingIndicator();
                final msg = _messages[index];
                final isUser = msg['role'] == 'user';
                final isError = msg['isError'] as bool? ?? false;
                return _buildMessageBubble(
                  text: msg['text'] as String,
                  isUser: isUser, isError: isError,
                  showRetry: isError && index == _messages.length - 1 && !_isLoading,
                );
              },
            ),
          ),
          if (_messages.length <= 2 && !_isLoading)
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              child: Row(
                children: _quickPrompts.map((p) => Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ActionChip(
                    label: Text(p, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: ThemeConfig.primary)),
                    backgroundColor: Colors.white,
                    side: const BorderSide(color: Color(0xFFBBF7D0)),
                    onPressed: () => _sendMessage(p),
                  ),
                )).toList(),
              ),
            ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: Color(0xFFE2E8F0)))),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Expanded(
                  child: TextField(
                    controller: _inputController,
                    textInputAction: TextInputAction.send,
                    maxLines: 4, minLines: 1,
                    onSubmitted: _sendMessage,
                    decoration: InputDecoration(
                      hintText: 'Tanya seputar POS & Kasir...',
                      hintStyle: TextStyle(fontSize: 13, color: Colors.grey.shade400),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      filled: true, fillColor: const Color(0xFFF1F5F9),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                GestureDetector(
                  onTap: _isLoading ? null : () => _sendMessage(_inputController.text),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 200),
                    padding: const EdgeInsets.all(11),
                    decoration: BoxDecoration(
                      color: _isLoading ? Colors.grey.shade300 : ThemeConfig.primary,
                      shape: BoxShape.circle,
                    ),
                    child: Icon(Icons.send_rounded, color: _isLoading ? Colors.grey.shade500 : Colors.white, size: 18),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTypingIndicator() {
    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.grey.shade200),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            AnimatedBuilder(
              animation: _dotController,
              builder: (_, __) => Row(
                children: List.generate(3, (i) {
                  final opacity = ((_dotController.value + i / 3) % 1.0) < 0.5 ? 1.0 : 0.3;
                  return Container(
                    margin: const EdgeInsets.symmetric(horizontal: 2),
                    width: 7, height: 7,
                    decoration: BoxDecoration(
                      color: ThemeConfig.primary.withValues(alpha: opacity),
                      shape: BoxShape.circle,
                    ),
                  );
                }),
              ),
            ),
            const SizedBox(width: 10),
            Text('AI sedang berpikir...', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
          ],
        ),
      ),
    );
  }

  Widget _buildMessageBubble({required String text, required bool isUser, required bool isError, bool showRetry = false}) {
    final baseColor = isUser ? Colors.white : isError ? Colors.red.shade700 : const Color(0xFF1E293B);
    final spans = <InlineSpan>[];
    final regex = RegExp(r'\*\*(.*?)\*\*|\*(.*?)\*');
    int lastEnd = 0;
    for (final match in regex.allMatches(text)) {
      if (match.start > lastEnd) spans.add(TextSpan(text: text.substring(lastEnd, match.start)));
      spans.add(TextSpan(text: match.group(1) ?? match.group(2), style: const TextStyle(fontWeight: FontWeight.bold)));
      lastEnd = match.end;
    }
    if (lastEnd < text.length) spans.add(TextSpan(text: text.substring(lastEnd)));

    return Align(
      alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Column(
        crossAxisAlignment: isUser ? CrossAxisAlignment.end : CrossAxisAlignment.start,
        children: [
          Container(
            margin: const EdgeInsets.only(bottom: 4),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.82),
            decoration: BoxDecoration(
              color: isUser ? ThemeConfig.primary : isError ? const Color(0xFFFFF3F3) : Colors.white,
              borderRadius: BorderRadius.circular(16).copyWith(
                topRight: isUser ? const Radius.circular(4) : const Radius.circular(16),
                topLeft: isUser ? const Radius.circular(16) : const Radius.circular(4),
              ),
              border: isUser ? null : Border.all(color: isError ? Colors.red.shade200 : Colors.grey.shade200),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 4, offset: const Offset(0, 2))],
            ),
            child: RichText(
              text: TextSpan(style: TextStyle(fontSize: 13, height: 1.5, color: baseColor), children: spans),
            ),
          ),
          if (showRetry)
            TextButton.icon(
              onPressed: _retryLastMessage,
              icon: const Icon(Icons.refresh_rounded, size: 14, color: ThemeConfig.primary),
              label: const Text('Coba Lagi', style: TextStyle(fontSize: 12, color: ThemeConfig.primary)),
            ),
          const SizedBox(height: 8),
        ],
      ),
    );
  }
}
