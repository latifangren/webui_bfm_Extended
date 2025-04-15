<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiDompul</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #7c4dff;
            --secondary-color: #b388ff;
            --dark-bg: #121212;
            --darker-bg: #1e1e1e;
            --card-bg: #2d2d2d;
            --text-color: #e0e0e0;
            --text-muted: #aaaaaa;
            --success-color: #66bb6a;
            --error-color: #ff7043;
            --info-color: #29b6f6;
        }
        
        body {
            touch-action: manipulation;
            overflow: auto;
            background-color: var(--dark-bg);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            line-height: 1.6;
        }
        
        .container {
            max-width: 500px;
            margin-top: 2rem;
            padding-bottom: 3rem;
        }
        
        .card {
            background-color: var(--card-bg);
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            overflow: auto;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            border-bottom: none;
            padding: 1.2rem;
            letter-spacing: 0.5px;
        }
        
        .form-label {
            color: white !important; /* White text for label */
        }
        
        .form-control, .form-control:focus {
            background-color: var(--darker-bg);
            color: var(--text-color);
            border: 1px solid #3d3d3d;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(124, 77, 255, 0.25);
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.7;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(124, 77, 255, 0.3);
        }
        
        .footer {
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 0.95rem;
            font-family: 'SF Mono', 'Roboto Mono', monospace;
            margin-bottom: 0;
            line-height: 1.5;
        }
        
        .logo {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .logo i {
            margin-right: 10px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem;
        }
        
        .alert-success {
            background-color: rgba(102, 187, 106, 0.2);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(255, 112, 67, 0.2);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }
        
        .alert-info {
            background-color: rgba(41, 182, 246, 0.2);
            color: var(--info-color);
            border-left: 4px solid var(--info-color);
        }
        
        #responseMessage {
            display: none;
            margin-top: 1.5rem;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .tagline {
            color: var(--text-muted);
            font-size: 1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center">
            <h1 class="logo">
                <i class="fas fa-mobile-alt"></i>SiDompul
            </h1>
            <p class="tagline">Cek kuota paket data AXIS/XL dengan mudah</p>
        </div>

        <div class="card">
            <div class="card-header text-center">
                <h4 class="mb-0"><i class="fas fa-search me-2"></i>Cek Paket Data</h4>
            </div>
            <div class="card-body">
                <form id="cekKuotaForm">
                    <div class="mb-3">
                        <label for="nomor" class="form-label">Masukkan Nomor HP</label>
                        <input type="nomor" id="nomor" name="nomor" class="form-control" placeholder="6287847076805" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane me-2"></i>Cek Paket
                    </button>
                </form>

                <div class="alert mt-3" id="responseMessage">
                    <pre id="resultText"></pre>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        © 2025 SiDompul | <i class="fas fa-code"></i> with <i class="fas fa-heart" style="color: #ff7043;"></i> JeelsBoobz
    </div>

    <script>
        document.getElementById("cekKuotaForm").addEventListener("submit", async function(event) {
            event.preventDefault();
            
            const nomor = document.getElementById("nomor").value.trim();
            const responseMessage = document.getElementById("responseMessage");
            const resultText = document.getElementById("resultText");
            const submitBtn = event.target.querySelector("button[type='submit']");
            
            // Save original button content
            const originalBtnContent = submitBtn.innerHTML;

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="loading-spinner"></span> Memproses...`;
            
            showResult("🔄 Memproses permintaan...", "info");

            try {
                let nomorHP = nomor.replace(/\D/g, "");

                // Convert 62 to 08 if needed
                if (/^62\d{9,12}$/.test(nomorHP)) {
                    nomorHP = "0" + nomorHP.substring(2);
                }

                // Validate number
                if (!/^08\d{8,11}$/.test(nomorHP)) {
                    showResult("❌ Format nomor tidak valid. Harus diawali dengan 08 atau 628 dan memiliki 10-13 digit", "danger");
                    return;
                }

                const response = await fetch(`https://apigw.kmsp-store.com/sidompul/v3/cek_kuota?msisdn=${nomorHP}&isJSON=true`, {
                    headers: {
                        "Authorization": "Basic c2lkb21wdWxhcGk6YXBpZ3drbXNw",
                        "X-API-Key": "4352ff7d-f4e6-48c6-89dd-21c811621b1c",
                        "X-App-Version": "3.0.0"
                    }
                });

                if (!response.ok) {
                    throw new Error("Gagal menghubungi server");
                }

                const data = await response.json();

                if (data.statusCode !== 200) {
                    showResult(`❌ ${data.message || "Gagal memproses"}`, "danger");
                } else {
                    const hasilKuota = data.data.hasil
                        .replace(/<br>/g, "\n")
                        .replace(/📲.*\n?/u, "")
                        .replace(/📃.*\n?/u, "")
                        .trim();

                    showResult(hasilKuota, "success");
                }
            } catch (error) {
                showResult(`❌ Error: ${error.message}`, "danger");
            } finally {
                // Restore button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
            }
        });

        function showResult(message, type) {
            const responseMessage = document.getElementById("responseMessage");
            const resultText = document.getElementById("resultText");
            
            resultText.textContent = message;
            responseMessage.className = `alert alert-${type}`;
            responseMessage.style.display = "block";
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>