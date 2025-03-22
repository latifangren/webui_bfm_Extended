#!/system/bin/sh
#t.me/vnzzxupig
# Buat log file untuk debugging
LOGFILE=/sdcard/vnstat.log

(
    # Tunggu sampai sistem selesai booting
    echo "$(date): Menunggu sistem boot..." >> $LOGFILE
    until [ "$(getprop sys.boot_completed)" = "1" ]; do
        sleep 2
    done

    # Tunggu Termux terinstall dan siap
    until [ -d "/data/data/com.termux/files" ]; do
        echo "$(date): Menunggu Termux..." >> $LOGFILE
        sleep 2
    done
    
    # Set environment variables
    export PATH=/data/data/com.termux/files/usr/bin:/system/bin:$PATH
    export LD_LIBRARY_PATH=/data/data/com.termux/files/usr/lib
    export LD_PRELOAD=/data/data/com.termux/files/usr/lib/libtermux-exec.so
    export PREFIX=/data/data/com.termux/files/usr
    export HOME=/data/data/com.termux/files/home

    # Tunggu beberapa detik untuk memastikan sistem sudah siap
    sleep 15
    
    echo "$(date): Mencoba menjalankan vnstatd..." >> $LOGFILE
    
    # Periksa apakah vnstatd sudah berjalan
    if pgrep vnstatd > /dev/null; then
        echo "$(date): vnstatd sudah berjalan" >> $LOGFILE
    else
        # Jalankan vnstatd
        su -c "/data/data/com.termux/files/usr/bin/vnstatd -d"
        echo "$(date): vnstatd dijalankan" >> $LOGFILE
    fi
)&
