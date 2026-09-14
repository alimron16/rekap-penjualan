import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../services/api_service.dart';
import '../services/notification_service.dart';
import '../utils/theme_config.dart';

class WebFeatureScreen extends StatefulWidget {
  final String title;
  final String path;

  const WebFeatureScreen({
    super.key,
    required this.title,
    required this.path,
  });

  @override
  State<WebFeatureScreen> createState() => _WebFeatureScreenState();
}

class _WebFeatureScreenState extends State<WebFeatureScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  double _progress = 0;

  @override
  void initState() {
    super.initState();
    _initWebView();
  }

  void _initWebView() async {
    final baseUrl = 'https://pos.moonbyte.my.id${widget.path}';

    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (int progress) {
            if (mounted) {
              setState(() {
                _progress = progress / 100;
              });
            }
          },
          onPageStarted: (String url) {
            if (mounted) {
              setState(() {
                _isLoading = true;
              });
            }
          },
          onPageFinished: (String url) {
            if (mounted) {
              setState(() {
                _isLoading = false;
              });
            }

            // Injeksi auto trigger notifikasi saat transaksi berhasil
            if (url.contains('receipt') || url.contains('success')) {
              NotificationService.showNotification(
                id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
                title: '✅ Transaksi Berhasil Diproses',
                body: 'Struk / data transaksi telah berhasil dicatat ke sistem!',
              );
            }
          },
          onWebResourceError: (WebResourceError error) {
            debugPrint('WebView Error: ${error.description}');
          },
        ),
      )
      ..loadRequest(Uri.parse(baseUrl));
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        if (await _controller.canGoBack()) {
          _controller.goBack();
          return false;
        }
        return true;
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text(
            widget.title,
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          ),
          backgroundColor: ThemeConfig.primary,
          actions: [
            IconButton(
              icon: const Icon(Icons.refresh),
              onPressed: () => _controller.reload(),
              tooltip: 'Muat Ulang',
            ),
          ],
        ),
        body: Stack(
          children: [
            WebViewWidget(controller: _controller),
            if (_isLoading)
              LinearProgressIndicator(
                value: _progress > 0 ? _progress : null,
                color: ThemeConfig.accent,
                backgroundColor: Colors.transparent,
                minHeight: 3,
              ),
          ],
        ),
      ),
    );
  }
}
