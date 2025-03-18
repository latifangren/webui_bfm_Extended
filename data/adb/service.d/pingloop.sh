#!/bin/ash

# Alamat host yang ingin Anda ping
HOST="quiz.vidio.com"

# Jumlah kegagalan ping sebelum mode pesawat diaktifkan
PING_FAIL_LIMIT=30

# Waktu tunggu (detik) sebelum menonaktifkan mode pesawat
WAIT_TIME=5

# Variabel untuk menghitung berapa kali ping gagal
failed_count=0

# Warna untuk output
GREEN="\033[0;32m"
RED="\033[0;31m"
NC="\033[0m"  # No Color

# Fungsi untuk mengaktifkan mode pesawat
enable_airplane_mode() {
    echo -e "$(date +"%Y-%m-%d %H:%M:%S") - ${RED}Mengaktifkan mode pesawat...${NC}"
    cmd connectivity airplane-mode enable
    settings put global airplane_mode_on 1
    am broadcast -a android.intent.action.AIRPLANE_MODE --ez state true
}

# Fungsi untuk menonaktifkan mode pesawat
disable_airplane_mode() {
    echo -e "$(date +"%Y-%m-%d %H:%M:%S") - ${GREEN}Menonaktifkan mode pesawat...${NC}"
    cmd connectivity airplane-mode disable
    settings put global airplane_mode_on 0
    am broadcast -a android.intent.action.AIRPLANE_MODE --ez state false
}

# Loop untuk melakukan ping dan mengaktifkan/menonaktifkan mode pesawat
while true; do
    # Melakukan ping ke host
    if ping -c 1 -W 2 $HOST > /dev/null; then
        echo -e "$(date +"%Y-%m-%d %H:%M:%S") - ${GREEN}Host dapat dijangkau${NC}"
        failed_count=0  # Reset hitungan kegagalan jika host berhasil dijangkau
    else
        echo -e "$(date +"%Y-%m-%d %H:%M:%S") - ${RED}Host tidak dapat dijangkau${NC}"
        failed_count=$((failed_count + 1))  # Tingkatkan hitungan kegagalan
        
        # Jika jumlah kegagalan mencapai batas
        if [ $failed_count -ge $PING_FAIL_LIMIT ]; then
            echo -e "$(date +"%Y-%m-%d %H:%M:%S") - ${RED}Gagal ping sebanyak $PING_FAIL_LIMIT kali. Mengaktifkan mode pesawat...${NC}"
            enable_airplane_mode  # Aktifkan mode pesawat
            sleep $WAIT_TIME  # Tunggu beberapa waktu
            echo -e "$(date +"%Y-%m-%d %H:%M:%S") - ${GREEN}Menonaktifkan mode pesawat kembali...${NC}"
            disable_airplane_mode  # Nonaktifkan mode pesawat
            failed_count=0  # Reset hitungan kegagalan setelah mode pesawat dinonaktifkan
        fi
    fi
    sleep 1  # Tunggu sebelum memeriksa koneksi lagi
done