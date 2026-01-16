<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

// Hitung total data
$total_buku = mysqli_fetch_array(mysqli_query($koneksi, "SELECT COUNT(*) as count FROM buku"))[0];
$total_anggota = mysqli_fetch_array(mysqli_query($koneksi, "SELECT COUNT(*) as count FROM anggota"))[0];
$total_pinjam = mysqli_fetch_array(mysqli_query($koneksi, "SELECT COUNT(*) as count FROM peminjaman"))[0];
$total_kembali = mysqli_fetch_array(mysqli_query($koneksi, "SELECT COUNT(*) as count FROM pengembalian"))[0];

// Hitung buku berdasarkan status
$buku_tersedia = mysqli_fetch_array(mysqli_query($koneksi, "SELECT COUNT(*) as count FROM buku WHERE status='Tersedia'"))[0];
$buku_dipinjam = mysqli_fetch_array(mysqli_query($koneksi, "SELECT COUNT(*) as count FROM buku WHERE status='Dipinjam'"))[0];

// Search/Filter Buku
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

$query_filter = "SELECT * FROM buku WHERE 1=1";
if ($search) {
    $query_filter .= " AND (judul LIKE '%$search%' OR penulis LIKE '%$search%')";
}
if ($filter_status) {
    $query_filter .= " AND status='$filter_status'";
}

$data_buku = mysqli_query($koneksi, $query_filter);

// Search Anggota
$search_anggota = isset($_GET['search_anggota']) ? $_GET['search_anggota'] : '';
$query_anggota = "SELECT * FROM anggota WHERE 1=1";
if ($search_anggota) {
    $query_anggota .= " AND (nama LIKE '%$search_anggota%' OR alamat LIKE '%$search_anggota%' OR telp LIKE '%$search_anggota%')";
}
$data_anggota = mysqli_query($koneksi, $query_anggota);

// Search Peminjaman (hanya yang belum dikembali)
$search_pinjam = isset($_GET['search_pinjam']) ? $_GET['search_pinjam'] : '';
$anggota_pk_search = 'id_anggota';
$acols_search = mysqli_query($koneksi, "SHOW COLUMNS FROM anggota");
if ($acols_search) {
    while ($ac = mysqli_fetch_assoc($acols_search)) {
        if ($ac['Field'] === 'id_anggota') {
            $anggota_pk_search = 'id_anggota';
            break;
        }
        if ($ac['Field'] === 'id') {
            $anggota_pk_search = 'id';
        }
    }
}

$buku_pk_search = 'id_buku';
$bcols_search = mysqli_query($koneksi, "SHOW COLUMNS FROM buku");
if ($bcols_search) {
    while ($bc = mysqli_fetch_assoc($bcols_search)) {
        if ($bc['Field'] === 'id_buku') {
            $buku_pk_search = 'id_buku';
            break;
        }
        if ($bc['Field'] === 'id') {
            $buku_pk_search = 'id';
        }
    }
}

$query_pinjam = "SELECT p.*, a.nama, b.judul FROM peminjaman p 
                 JOIN anggota a ON p.id_anggota = a.$anggota_pk_search 
                 JOIN buku b ON p.id_buku = b.$buku_pk_search 
                 WHERE p.tanggal_kembali IS NULL";
if ($search_pinjam) {
    $query_pinjam .= " AND (a.nama LIKE '%$search_pinjam%' OR b.judul LIKE '%$search_pinjam%')";
}
$data_pinjam = mysqli_query($koneksi, $query_pinjam);

// Search Pengembalian
$search_kembali = isset($_GET['search_kembali']) ? $_GET['search_kembali'] : '';
$query_kembali = "SELECT k.*, a.nama, b.judul FROM pengembalian k 
                  JOIN anggota a ON k.id_anggota = a.$anggota_pk_search 
                  JOIN buku b ON k.id_buku = b.$buku_pk_search";
if ($search_kembali) {
    $query_kembali .= " WHERE a.nama LIKE '%$search_kembali%' OR b.judul LIKE '%$search_kembali%'";
}
$query_kembali .= " ORDER BY k.tanggal DESC LIMIT 20";
$data_kembali = mysqli_query($koneksi, $query_kembali);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Perpustakaan</title>
    <link rel="stylesheet" href="assets/dashboard.css">
    <script defer src="assets/animation.js"></script>
</head>

<body>

    <div class="container">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <img src="assets/logo.png" alt="Library Logo" class="sidebar-logo">
            <h2>YuPerpus</h2>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="buku.php">Data Buku</a>
            <a href="anggota.php">Data Anggota</a>
            <a href="pinjam.php">Peminjaman</a>
            <a href="kembali.php">Pengembalian</a>
            <a href="logout.php">Logout</a>
        </div>

        <!-- CONTENT -->
        <div class="content normal-bg">

            <!-- WELCOME CARD -->
            <div class="card">
                <h2>Selamat Datang 👋</h2>
                <p>Login sebagai <b><?= $_SESSION['admin']; ?></b></p>
            </div>

            <!-- INFO CARDS -->
            <div class="info-grid">
                <div class="card">
                    <h3>📘 Data Buku</h3>
                    <p>Kelola koleksi buku perpustakaan</p>
                    <div class="total-count">Total: <?= $total_buku; ?> Buku</div>
                </div>

                <div class="card">
                    <h3>👤 Data Anggota</h3>
                    <p>Kelola data anggota perpustakaan</p>
                    <div class="total-count">Total: <?= $total_anggota; ?> Anggota</div>
                </div>

                <div class="card">
                    <h3>🔄 Peminjaman</h3>
                    <p>Kelola transaksi peminjaman buku</p>
                    <div class="total-count">Total: <?= $total_pinjam; ?> Transaksi</div>
                </div>

                <div class="card">
                    <h3>↩️ Pengembalian</h3>
                    <p>Kelola data pengembalian buku</p>
                    <div class="total-count">Total: <?= $total_kembali; ?> Pengembalian</div>
                </div>
            </div>

            <!-- STATISTIK BUKU STATUS -->
            <div class="card">
                <h2>📊 Status Buku</h2>
                <div class="status-grid">
                    <div class="status-box tersedia">
                        <h3><?= $buku_tersedia; ?></h3>
                        <p>Buku Tersedia</p>
                    </div>
                    <div class="status-box dipinjam">
                        <h3><?= $buku_dipinjam; ?></h3>
                        <p>Buku Dipinjam</p>
                    </div>
                </div>
            </div>

            <!-- SEARCH & FILTER BUKU -->
            <div class="card">
                <h2>🔍 Cari Buku</h2>
                <form method="get" class="search-form">
                    <input type="text" name="search" placeholder="Cari judul atau penulis..." autocomplete="off"
                        value="<?= $search; ?>">
                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="Tersedia" <?= $filter_status == 'Tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                        <option value="Dipinjam" <?= $filter_status == 'Dipinjam' ? 'selected' : ''; ?>>Dipinjam</option>
                    </select>
                    <button type="submit">Cari</button>
                    <a href="dashboard.php" class="btn-reset">Reset</a>
                </form>

                <div class="card table-card">
                    <h3>Hasil Pencarian</h3>
                    <table>
                        <tr>
                            <th>Judul</th>
                            <th>Penulis</th>
                            <th>Tahun</th>
                            <th>Status</th>
                        </tr>
                        <?php
                        if (mysqli_num_rows($data_buku) > 0) {
                            while ($d = mysqli_fetch_array($data_buku)) {
                                $status_class = ($d['status'] == 'Tersedia') ? 'status-ok' : 'status-pinjam';
                                echo "<tr>
                                    <td>$d[judul]</td>
                                    <td>$d[penulis]</td>
                                    <td>$d[tahun]</td>
                                    <td><span class='$status_class'>$d[status]</span></td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; color:#999;'>Tidak ada data ditemukan</td></tr>";
                        }
                        ?>
                    </table>
                </div>
            </div>

            <!-- SEARCH & FILTER ANGGOTA -->
            <div class="card">
                <h2>🔍 Cari Anggota</h2>
                <form method="get" class="search-form">
                    <input type="text" name="search_anggota" placeholder="Cari nama, alamat, atau telepon..."
                        value="<?= $search_anggota; ?>">
                    <button type="submit">Cari</button>
                    <a href="dashboard.php" class="btn-reset">Reset</a>
                </form>

                <div class="card table-card">
                    <h3>Hasil Pencarian Anggota</h3>
                    <table>
                        <tr>
                            <th>Nama</th>
                            <th>Alamat</th>
                            <th>Telepon</th>
                        </tr>
                        <?php
                        if (mysqli_num_rows($data_anggota) > 0) {
                            while ($d = mysqli_fetch_array($data_anggota)) {
                                echo "<tr>
                                    <td>$d[nama]</td>
                                    <td>$d[alamat]</td>
                                    <td>$d[telp]</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center; color:#999;'>Tidak ada anggota ditemukan</td></tr>";
                        }
                        ?>
                    </table>
                </div>
            </div>

            <!-- SEARCH & FILTER PEMINJAMAN AKTIF -->
            <div class="card">
                <h2>🔍 Cari Peminjaman Aktif</h2>
                <form method="get" class="search-form">
                    <input type="text" name="search_pinjam" placeholder="Cari nama anggota atau judul buku..."
                        value="<?= $search_pinjam; ?>">
                    <button type="submit">Cari</button>
                    <a href="dashboard.php" class="btn-reset">Reset</a>
                </form>

                <div class="card table-card">
                    <h3>Peminjaman Aktif (Belum Dikembalikan)</h3>
                    <table>
                        <tr>
                            <th>Anggota</th>
                            <th>Buku</th>
                            <th>Tanggal Pinjam</th>
                        </tr>
                        <?php
                        if (mysqli_num_rows($data_pinjam) > 0) {
                            while ($d = mysqli_fetch_array($data_pinjam)) {
                                echo "<tr>
                                    <td>$d[nama]</td>
                                    <td>$d[judul]</td>
                                    <td>$d[tanggal_pinjam]</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center; color:#999;'>Tidak ada peminjaman aktif</td></tr>";
                        }
                        ?>
                    </table>
                </div>
            </div>

            <!-- SEARCH & FILTER PENGEMBALIAN -->
            <div class="card">
                <h2>🔍 Cari Pengembalian</h2>
                <form method="get" class="search-form">
                    <input type="text" name="search_kembali" placeholder="Cari nama anggota atau judul buku..."
                        value="<?= $search_kembali; ?>">
                    <button type="submit">Cari</button>
                    <a href="dashboard.php" class="btn-reset">Reset</a>
                </form>

                <div class="card table-card">
                    <h3>Riwayat Pengembalian (20 Terbaru)</h3>
                    <table>
                        <tr>
                            <th>Anggota</th>
                            <th>Buku</th>
                            <th>Tanggal Kembali</th>
                        </tr>
                        <?php
                        if (mysqli_num_rows($data_kembali) > 0) {
                            while ($d = mysqli_fetch_array($data_kembali)) {
                                echo "<tr>
                                    <td>$d[nama]</td>
                                    <td>$d[judul]</td>
                                    <td>$d[tanggal]</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center; color:#999;'>Tidak ada data pengembalian</td></tr>";
                        }
                        ?>
                    </table>
                </div>
            </div>

        </div>

    </div>

</body>

</html>