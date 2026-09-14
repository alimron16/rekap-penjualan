import os
import sys
import subprocess
import shutil
import zipfile

PROJECT_DIR = r"D:\laravel\rekap-penjualan"
APP_DIR = os.path.join(PROJECT_DIR, "android-app")
SRC_DIR = os.path.join(APP_DIR, "src", "main")
RES_DIR = os.path.join(SRC_DIR, "res")
JAVA_DIR = os.path.join(SRC_DIR, "java")
MANIFEST = os.path.join(SRC_DIR, "AndroidManifest.xml")

BUILD_DIR = os.path.join(APP_DIR, "build")
COMPILED_RES_DIR = os.path.join(BUILD_DIR, "compiled_res")
GEN_DIR = os.path.join(BUILD_DIR, "gen")
CLASSES_DIR = os.path.join(BUILD_DIR, "classes")
DEX_DIR = os.path.join(BUILD_DIR, "dex")

SDK_DIR = r"C:\Users\Imr\AppData\Local\Android\Sdk"
BUILD_TOOLS_DIR = os.path.join(SDK_DIR, "build-tools", "35.0.0")
PLATFORM_JAR = os.path.join(SDK_DIR, "platforms", "android-34", "android.jar")

AAPT2 = os.path.join(BUILD_TOOLS_DIR, "aapt2.exe")
D8_JAR = os.path.join(BUILD_TOOLS_DIR, "lib", "d8.jar")
ZIPALIGN = os.path.join(BUILD_TOOLS_DIR, "zipalign.exe")
APKSIGNER_JAR = os.path.join(BUILD_TOOLS_DIR, "lib", "apksigner.jar")

JDK_BIN = r"C:\Program Files\Java\jdk-22\bin"
JAVA = os.path.join(JDK_BIN, "java.exe")
JAVAC = os.path.join(JDK_BIN, "javac.exe")
KEYTOOL = os.path.join(JDK_BIN, "keytool.exe")

KEYSTORE = os.path.join(BUILD_DIR, "debug.keystore")
KEY_ALIAS = "androiddebugkey"
KEY_PASS = "android"

OUTPUT_APK = os.path.join(PROJECT_DIR, "elephant-pos.apk")
PUBLIC_DOWNLOAD_DIR = os.path.join(PROJECT_DIR, "public", "download")
PUBLIC_APK = os.path.join(PUBLIC_DOWNLOAD_DIR, "elephant-pos.apk")

def run(cmd, desc):
    print(f"==> {desc}...")
    res = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    if res.returncode != 0:
        print(f"FAILED: {desc}")
        print("STDOUT:", res.stdout)
        print("STDERR:", res.stderr)
        sys.exit(1)
    return res.stdout

def clean_build():
    if os.path.exists(BUILD_DIR):
        shutil.rmtree(BUILD_DIR)
    os.makedirs(COMPILED_RES_DIR, exist_ok=True)
    os.makedirs(GEN_DIR, exist_ok=True)
    os.makedirs(CLASSES_DIR, exist_ok=True)
    os.makedirs(DEX_DIR, exist_ok=True)
    os.makedirs(PUBLIC_DOWNLOAD_DIR, exist_ok=True)

def step1_compile_res():
    # Compile each resource file
    res_files = []
    for root, dirs, files in os.walk(RES_DIR):
        for f in files:
            full_path = os.path.join(root, f)
            res_files.append(full_path)
    
    cmd = f'"{AAPT2}" compile --dir "{RES_DIR}" -o "{COMPILED_RES_DIR}"'
    run(cmd, "AAPT2 compiling resources")

def step2_link_res():
    # Gather compiled .flat files
    flat_files = [os.path.join(COMPILED_RES_DIR, f) for f in os.listdir(COMPILED_RES_DIR) if f.endswith(".flat")]
    flat_args = " ".join([f'"{f}"' for f in flat_files])
    unaligned_apk = os.path.join(BUILD_DIR, "unaligned_res.apk")
    
    cmd = (
        f'"{AAPT2}" link -I "{PLATFORM_JAR}" '
        f'--manifest "{MANIFEST}" '
        f'{flat_args} '
        f'-o "{unaligned_apk}" '
        f'--java "{GEN_DIR}" '
        f'--auto-add-overlay'
    )
    run(cmd, "AAPT2 linking resources and generating R.java")
    return unaligned_apk

def step3_compile_java():
    # Find all java files in JAVA_DIR and GEN_DIR
    java_files = []
    for root, dirs, files in os.walk(JAVA_DIR):
        for f in files:
            if f.endswith(".java"):
                java_files.append(os.path.join(root, f))
    for root, dirs, files in os.walk(GEN_DIR):
        for f in files:
            if f.endswith(".java"):
                java_files.append(os.path.join(root, f))
                
    files_arg = " ".join([f'"{f}"' for f in java_files])
    cmd = f'"{JAVAC}" -encoding UTF-8 -cp "{PLATFORM_JAR}" -d "{CLASSES_DIR}" {files_arg}'
    run(cmd, "Compiling Java sources with javac")

def step4_dex():
    # Find all .class files
    class_files = []
    for root, dirs, files in os.walk(CLASSES_DIR):
        for f in files:
            if f.endswith(".class"):
                class_files.append(os.path.join(root, f))
                
    files_arg = " ".join([f'"{f}"' for f in class_files])
    cmd = f'"{JAVA}" -cp "{D8_JAR}" com.android.tools.r8.D8 --lib "{PLATFORM_JAR}" --output "{DEX_DIR}" {files_arg}'
    run(cmd, "D8 dexing class files")

def step5_package_dex(unaligned_apk):
    # Add classes.dex into unaligned_apk
    dex_file = os.path.join(DEX_DIR, "classes.dex")
    target_apk = os.path.join(BUILD_DIR, "unaligned_with_dex.apk")
    
    print("==> Packaging classes.dex into APK...")
    shutil.copyfile(unaligned_apk, target_apk)
    
    with zipfile.ZipFile(target_apk, 'a') as zf:
        zf.write(dex_file, "classes.dex")
    return target_apk

def step6_zipalign(apk_with_dex):
    aligned_apk = os.path.join(BUILD_DIR, "aligned.apk")
    cmd = f'"{ZIPALIGN}" -f -p 4 "{apk_with_dex}" "{aligned_apk}"'
    run(cmd, "Zipaligning APK")
    return aligned_apk

def step7_sign(aligned_apk):
    # Check or generate debug keystore
    if not os.path.exists(KEYSTORE):
        cmd_key = (
            f'"{KEYTOOL}" -genkeypair -v '
            f'-keystore "{KEYSTORE}" '
            f'-alias "{KEY_ALIAS}" '
            f'-keyalg RSA -keysize 2048 -validity 10000 '
            f'-storepass "{KEY_PASS}" -keypass "{KEY_PASS}" '
            f'-dname "CN=ElephantCell, OU=POS, O=ElephantCellGroup, L=Bekasi, ST=JawaBarat, C=ID"'
        )
        run(cmd_key, "Generating debug signing keystore")
        
    final_apk = os.path.join(BUILD_DIR, "elephant-pos-signed.apk")
    cmd_sign = (
        f'"{JAVA}" -jar "{APKSIGNER_JAR}" sign '
        f'--ks "{KEYSTORE}" '
        f'--ks-key-alias "{KEY_ALIAS}" '
        f'--ks-pass "pass:{KEY_PASS}" '
        f'--key-pass "pass:{KEY_PASS}" '
        f'--out "{final_apk}" '
        f'"{aligned_apk}"'
    )
    run(cmd_sign, "Signing APK with apksigner")
    
    # Copy to project root and public/download
    shutil.copyfile(final_apk, OUTPUT_APK)
    shutil.copyfile(final_apk, PUBLIC_APK)
    size_mb = os.path.getsize(OUTPUT_APK) / (1024 * 1024)
    print(f"\n=======================================================")
    print(f" SUCCESS! Android APK generated successfully!")
    print(f" File 1: {OUTPUT_APK} ({size_mb:.2f} MB)")
    print(f" File 2: {PUBLIC_APK} ({size_mb:.2f} MB)")
    print(f" Public Download URL: https://pos.moonbyte.my.id/download/elephant-pos.apk")
    print(f"=======================================================\n")

if __name__ == "__main__":
    clean_build()
    step1_compile_res()
    unaligned = step2_link_res()
    step3_compile_java()
    step4_dex()
    with_dex = step5_package_dex(unaligned)
    aligned = step6_zipalign(with_dex)
    step7_sign(aligned)
