<?php
$page_title = 'QR Code Generator - QR Menu System';

// Check if user is logged in and setup is complete
if (!isset($_SESSION['user_id'])) {
    header('Location: /QR-Menu/login');
    exit();
}

if (!$_SESSION['setup_complete']) {
    header('Location: /QR-Menu/setup');
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get vendor information
$stmt = $db->prepare("SELECT * FROM vendors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    header('Location: /QR-Menu/setup');
    exit();
}

$menu_url = "https://qr-menu.42web.io/" . $vendor['username'];

include 'includes/header.php';
?>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">QR Code Generator</h1>
            <p class="mt-2 text-gray-600">Generate and download QR codes for your digital menu</p>
        </div>

        <!-- QR Code Display -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- QR Code Preview -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Your Menu URL</h3>
                    <div class="flex items-center space-x-2">
                        <input type="text" value="<?php echo htmlspecialchars($menu_url); ?>" 
                               class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm bg-gray-50" readonly>
                        <button type="button" onclick="copyToClipboard('<?php echo htmlspecialchars($menu_url); ?>')" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md text-sm">
                            <i class="fas fa-copy mr-1"></i>Copy
                        </button>
                    </div>
                </div>

                <!-- Design Options -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Design Style</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" onclick="changeDesign('classic')" id="design-classic" class="design-style-btn border-2 border-blue-600 bg-blue-50 p-3 rounded-lg text-center">
                            <i class="fas fa-clipboard-list text-2xl text-blue-600 mb-2"></i>
                            <p class="text-sm font-medium text-blue-600">Classic</p>
                        </button>
                        <button type="button" onclick="changeDesign('modern')" id="design-modern" class="design-style-btn border-2 border-gray-300 p-3 rounded-lg text-center">
                            <i class="fas fa-bolt text-2xl text-gray-600 mb-2"></i>
                            <p class="text-sm font-medium text-gray-600">Modern</p>
                        </button>
                    </div>
                </div>

                <!-- Optional Header/Footer Inputs -->
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Header (optional)</label>
                        <input type="text" id="qrHeaderInput" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="e.g. Scan to view our menu" oninput="updateHeaderFooter()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Footer (optional)</label>
                        <input type="text" id="qrFooterInput" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="e.g. Thank you for visiting!" oninput="updateHeaderFooter()">
                    </div>
                </div>

                <!-- QR Code Display -->
                <div class="text-center">
                    <div id="qrCodeContainer" class="inline-block p-4 bg-white border-2 border-gray-200 rounded-lg">
                        <!-- Dynamic QR design preview will be rendered here -->
                    </div>
                </div>
            </div>

            <!-- Download Options -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Download Options</h3>
                
                <!-- Download as PNG -->
                <div class="mb-6">
                    <h4 class="text-md font-medium text-gray-700 mb-3">Download as Image</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" onclick="downloadQR('png', 'small')" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                            <i class="fas fa-download mr-2"></i>Small (256x256)
                        </button>
                        <button type="button" onclick="downloadQR('png', 'large')" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                            <i class="fas fa-download mr-2"></i>Large (512x512)
                        </button>
                    </div>
                </div>

                <!-- Download as PDF -->
                <div class="mb-6">
                    <h4 class="text-md font-medium text-gray-700 mb-3">Download as PDF</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" onclick="downloadQR('pdf', 'a4')" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">
                            <i class="fas fa-file-pdf mr-2"></i>A4 Size
                        </button>
                        <button type="button" onclick="downloadQR('pdf', 'business')" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">
                            <i class="fas fa-file-pdf mr-2"></i>Business Card
                        </button>
                    </div>
                </div>

                <!-- Usage Tips -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-md font-medium text-blue-900 mb-2">Usage Tips</h4>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• Print and place QR codes on tables, menus, or walls</li>
                        <li>• Use the business card size for easy distribution</li>
                        <li>• The sticker size is perfect for table tents</li>
                        <li>• Customers can scan with any smartphone camera</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- QR Code Analytics -->
        <div class="mt-8 bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">QR Code Analytics</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <?php
                // Get QR scan statistics
                $stmt = $db->prepare("SELECT COUNT(*) as total_scans FROM qr_scans WHERE vendor_id = ?");
                $stmt->execute([$vendor['id']]);
                $total_scans = $stmt->fetch(PDO::FETCH_ASSOC)['total_scans'];

                $stmt = $db->prepare("SELECT COUNT(*) as today_scans FROM qr_scans WHERE vendor_id = ? AND DATE(scanned_at) = CURDATE()");
                $stmt->execute([$vendor['id']]);
                $today_scans = $stmt->fetch(PDO::FETCH_ASSOC)['today_scans'];

                $stmt = $db->prepare("SELECT COUNT(*) as week_scans FROM qr_scans WHERE vendor_id = ? AND scanned_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                $stmt->execute([$vendor['id']]);
                $week_scans = $stmt->fetch(PDO::FETCH_ASSOC)['week_scans'];

                $stmt = $db->prepare("SELECT COUNT(*) as month_scans FROM qr_scans WHERE vendor_id = ? AND scanned_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $stmt->execute([$vendor['id']]);
                $month_scans = $stmt->fetch(PDO::FETCH_ASSOC)['month_scans'];
                ?>
                
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600"><?php echo number_format($total_scans); ?></div>
                    <div class="text-sm text-blue-800">Total Scans</div>
                </div>
                
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600"><?php echo number_format($today_scans); ?></div>
                    <div class="text-sm text-green-800">Today</div>
                </div>
                
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600"><?php echo number_format($week_scans); ?></div>
                    <div class="text-sm text-yellow-800">This Week</div>
                </div>
                
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600"><?php echo number_format($month_scans); ?></div>
                    <div class="text-sm text-purple-800">This Month</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentStyle = 'default';
let currentDesign = 'classic';

const menuUrl = '<?php echo htmlspecialchars($menu_url); ?>';
const businessName = '<?php echo addslashes($vendor['business_name']); ?>';

let qrHeader = '';
let qrFooter = '';

function updateHeaderFooter() {
    qrHeader = document.getElementById('qrHeaderInput').value;
    qrFooter = document.getElementById('qrFooterInput').value;
    renderQRDesign();
}

// Render the QR design preview
function renderQRDesign() {
    const container = document.getElementById('qrCodeContainer');
    let html = '';
    let headerHtml = qrHeader ? `<div class="text-lg font-semibold mb-2">${qrHeader}</div>` : '';
    let footerHtml = qrFooter ? `<div class="text-sm text-gray-600 mt-2">${qrFooter}</div>` : '';
    // Center the QR image using flexbox
    const qrImgTag = `
        <div class="flex justify-center mb-2">
            <img id="qrImg" 
                 src="https://api.qrserver.com/v1/create-qr-code/?data=${encodeURIComponent(menuUrl)}&size=256x256" 
                 width="256" height="256" alt="QR Code" crossorigin="anonymous">
        </div>
    `;
    if (currentDesign === 'classic') {
        html = `
            <div class="qr-design-classic">
                ${headerHtml}
                ${!qrHeader ? `<div class="text-xl font-bold mb-0.5">Welcome to ${businessName}</div>` : ''}
                <p style="font-size: 1.1rem; color:rgb(36, 34, 34); font-weight: 500; margin-bottom: 0.5rem; margin-top: 0.2rem;">Scan to view our menu</p>
                ${qrImgTag}
                <div class="text-sm text-gray-700">${menuUrl}</div>
                ${footerHtml}
                <span class="text-xs italic text-gray-500">Powered by QR-Menu.42web.io</span>
            </div>
        `;
    } else if (currentDesign === 'modern') {
        html = `
            <div class="qr-design-modern">
                ${headerHtml}
                ${!qrHeader ? `<div class="text-2xl font-extrabold text-blue-700 mb-2">Scan to Order</div>` : ''}
                ${qrImgTag}
                <div class="text-base font-semibold text-gray-900">${businessName}</div>
                <div class="text-xs text-gray-500 mt-1">${menuUrl}</div>
                ${footerHtml}
                <span class="text-xs italic text-gray-500">Powered by QR-Menu.42web.io</span>  
            </div>
        `;
    }
    container.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    renderQRDesign();
});

function changeDesign(design) {
    currentDesign = design;
    document.querySelectorAll('.design-style-btn').forEach(btn => {
        btn.classList.remove('border-blue-600', 'bg-blue-50');
        btn.classList.add('border-gray-300');
        btn.querySelector('i').classList.remove('text-blue-600');
        btn.querySelector('i').classList.add('text-gray-600');
        btn.querySelector('p').classList.remove('text-blue-600');
        btn.querySelector('p').classList.add('text-gray-600');
    });
    const selectedBtn = document.getElementById('design-' + design);
    selectedBtn.classList.remove('border-gray-300');
    selectedBtn.classList.add('border-blue-600', 'bg-blue-50');
    selectedBtn.querySelector('i').classList.remove('text-gray-600');
    selectedBtn.querySelector('i').classList.add('text-blue-600');
    selectedBtn.querySelector('p').classList.remove('text-gray-600');
    selectedBtn.querySelector('p').classList.add('text-blue-600');
    renderQRDesign();
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
        button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        button.classList.add('bg-green-600');
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('bg-green-600');
            button.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }, 2000);
    });
}

function downloadQR(format, size) {
    const img = document.getElementById('qrImg');
    let filename = `qr-menu-${size}.${format}`;
    const captureAndDownload = () => {
        html2canvas(document.getElementById('qrCodeContainer'), { useCORS: true }).then(canvas => {
            if (format === 'png') {
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else if (format === 'pdf') {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                const imgProps = doc.getImageProperties(imgData);
                const pdfWidth = pageWidth - 40;
                const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                const x = 20;
                const y = (pageHeight - pdfHeight) / 2;
                doc.addImage(imgData, 'PNG', x, y, pdfWidth, pdfHeight);
                doc.save(filename);
            }
        });
    };
    // If the image is not loaded, wait for it
    if (!img.complete || img.naturalWidth === 0) {
        img.onload = function() {
            captureAndDownload();
        };
        // Optionally, handle error
        img.onerror = function() {
            alert('QR code image failed to load. Please try again.');
        };
    } else {
        captureAndDownload();
    }
}
</script>

<?php include 'includes/footer.php'; ?> 
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script> 