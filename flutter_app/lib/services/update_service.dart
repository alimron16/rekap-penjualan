import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import 'api_service.dart';
import '../utils/theme_config.dart';

class UpdateService {
  static const String currentVersion = '1.0.7';
  static const int currentVersionCode = 8;
  static const String _keyLastDismissed = 'app_update_dismissed_time';

  /// Check server for app updates
  static Future<void> checkUpdate(BuildContext context, {bool autoPrompt = false}) async {
    try {
      final res = await ApiService.getAppVersion();
      if (res['success'] != true) return;

      final serverVersion = res['version']?.toString() ?? '1.0.0';
      final serverCode = int.tryParse(res['version_code']?.toString() ?? '0') ?? 0;
      final isNewer = serverCode > currentVersionCode || _isVersionGreater(serverVersion, currentVersion);

      if (!context.mounted) return;

      if (isNewer) {
        final isForce = res['force_update'] == true;
        if (autoPrompt && !isForce) {
          final prefs = await SharedPreferences.getInstance();
          final lastDismissed = prefs.getInt(_keyLastDismissed) ?? 0;
          final now = DateTime.now().millisecondsSinceEpoch;
          // Don't auto-prompt if dismissed within the last 4 hours
          if (now - lastDismissed < 4 * 3600 * 1000) {
            return;
          }
        }

        if (context.mounted) {
          showUpdateDialog(context, res);
        }
      } else {
        if (!autoPrompt && context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Aplikasi sudah menggunakan versi terbaru (v$currentVersion).'),
              backgroundColor: ThemeConfig.primary,
              duration: const Duration(seconds: 3),
            ),
          );
        }
      }
    } catch (e) {
      if (!autoPrompt && context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gagal memeriksa pembaruan: $e'),
            backgroundColor: Colors.red.shade700,
          ),
        );
      }
    }
  }

  /// Compare two semantic version strings (e.g. "1.0.2" vs "1.0.1")
  static bool _isVersionGreater(String vServer, String vCurrent) {
    try {
      final sParts = vServer.split('.').map(int.parse).toList();
      final cParts = vCurrent.split('.').map(int.parse).toList();
      final length = sParts.length < cParts.length ? sParts.length : cParts.length;

      for (int i = 0; i < length; i++) {
        if (sParts[i] > cParts[i]) return true;
        if (sParts[i] < cParts[i]) return false;
      }
      return sParts.length > cParts.length;
    } catch (_) {
      return vServer != vCurrent;
    }
  }

  /// Show modern update modal dialog
  static void showUpdateDialog(BuildContext context, Map<String, dynamic> updateData) {
    final version = updateData['version'] ?? 'Terbaru';
    final fileSize = updateData['file_size'] ?? '';
    final notes = updateData['release_notes']?.toString() ?? 'Peningkatan stabilitas dan performa aplikasi.';
    final downloadUrl = updateData['download_url']?.toString() ?? 'https://pos.moonbyte.my.id/download-apk';

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        contentPadding: const EdgeInsets.all(22),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: ThemeConfig.primary.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.system_update_rounded, color: ThemeConfig.primary, size: 28),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Pembaruan Tersedia',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Versi $version ${fileSize.isNotEmpty ? "($fileSize)" : ""}',
                        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Colors.grey.shade700),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            const Text(
              'Apa yang baru:',
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black87),
            ),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: Text(
                notes,
                style: const TextStyle(fontSize: 12, height: 1.45, color: Colors.black87),
              ),
            ),
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () async {
                      Navigator.pop(ctx);
                      final prefs = await SharedPreferences.getInstance();
                      await prefs.setInt(_keyLastDismissed, DateTime.now().millisecondsSinceEpoch);
                    },
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      side: BorderSide(color: Colors.grey.shade400),
                    ),
                    child: const Text('Lain Kali', style: TextStyle(color: Colors.black87, fontWeight: FontWeight.w600)),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () async {
                      Navigator.pop(ctx);
                      final uri = Uri.parse(downloadUrl);
                      try {
                        final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
                        if (!launched) {
                          await launchUrl(uri, mode: LaunchMode.platformDefault);
                        }
                      } catch (e) {
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text('Gagal membuka browser: $e. Silakan download manual via link.'), backgroundColor: Colors.red),
                          );
                        }
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: ThemeConfig.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      elevation: 0,
                    ),
                    child: const Text('Update Sekarang', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
