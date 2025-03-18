#!/bin/ash

# Alamat host yang ingin Anda ping
HOST="quiz.vidio.com"

# Jumlah kegagalan ping sebelum mode pesawat diaktifkan
PING_FAIL_LIMIT=10

# Waktu tunggu (detik) sebelum ganti apn
WAIT_TIME=5

# Variabel untuk menghitung berapa kali ping gagal
failed_count=0

# Warna untuk output
GREEN="\033[0;32m"
RED="\033[0;31m"
NC="\033[0m"  # No Color

# ID APN yang ingin Anda gunakan
ID_APN="2910"  
ID_APN2="3439" 
#contoh nama apn vidio id nya 2910
#contoh nama apn vidio1 id nya 3439
# Ganti ini dengan ID APN yang sesuai hp kalian
#cari id APN cari pake:
# su -c content query --uri content://telephony/carriers | grep vidio (vidio di ganti dengan nama APN di hp mu)
# Fungsi untuk mengaktifkan mode dengan APN tertentu
enable_apn_mode() {
    echo -e "$(date +"%Y-%m-%d %H:%M:%S") - ${RED}Mengaktifkan mode APN...${NC}"
    su -c "content update --uri content://telephony/carriers/preferapn --bind apn_id:i:$ID_APN"
}

# Fungsi untuk menonaktifkan mode APN
disable_apn_mode() {
    echo -e "$(date +"%Y-%m-%d %H:%M:%S") - ${GREEN}Menonaktifkan mode APN...${NC}"
    # Anda bisa memasukkan perintah yang sesuai untuk menonaktifkan APN jika diperlukan.
    # Misalnya, mengganti ke APN default atau tidak ada.
    su -c "content update --uri content://telephony/carriers/preferapn --bind apn_id:i:$ID_APN2"
}

# Loop untuk melakukan ping dan mengaktifkan/menonaktifkan APN
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
            echo -e "$(date +"%Y-%m-%d %H:%M:%S") - ${RED}Gagal ping sebanyak $PING_FAIL_LIMIT kali. Mengaktifkan mode APN...${NC}"
            enable_apn_mode  # Aktifkan mode APN
            sleep $WAIT_TIME  # Tunggu beberapa waktu
            echo -e "$(date +"%Y-%m-%d %H:%M:%S") - ${GREEN}Menonaktifkan mode APN kembali...${NC}"
            disable_apn_mode  # Nonaktifkan mode APN
            failed_count=0  # Reset hitungan kegagalan setelah mode APN dinonaktifkan
        fi
    fi
    sleep 1  # Tunggu sebelum memeriksa koneksi lagi
done