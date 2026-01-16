<?php
session_start();
if (!isset($_SESSION['admin']))
    header("Location: login.php");
include 'koneksi.php';
?>
<!DOCTYPE html>
<html>

<head>
    <title>Pengembalian</title>
    <link rel="stylesheet" href="assets/dashboard.css">
    <script defer src="assets/animation.js"></script>
</head>

<body>

    <div class="container">
        <div class="sidebar">
            <img src="assets/logo.png" alt="Library Logo" class="sidebar-logo">
            <h2>YuPerpus</h2>
            <a href="dashboard.php">Dashboard</a>
            <a href="buku.php">Data Buku</a>
            <a href="anggota.php">Data Anggota</a>
            <a href="pinjam.php">Peminjaman</a>
            <a href="kembali.php" class="active">Pengembalian</a>
            <a href="logout.php">Logout</a>
        </div>

        <div class="content normal-bg">

            <?php
            if (isset($success)) {
                echo "<div class='success'>✓ " . htmlspecialchars($success) . "</div>";
            }
            if (isset($error)) {
                echo "<div class='error'>✗ " . htmlspecialchars($error) . "</div>";
            }
            ?>

            <div class="card form-card">
                <h2>Pengembalian Buku</h2>
                <form method="post" autocomplete="off">
                    <select name="pinjam" required>
                        <option value="">Pilih Transaksi</option>
                        <?php
                        // Deteksi primary key pada tabel peminjaman (id_pinjam atau id)
                        $pinjam_pk = 'id';
                        $pcols = mysqli_query($koneksi, "SHOW COLUMNS FROM peminjaman");
                        if ($pcols) {
                            while ($pc = mysqli_fetch_assoc($pcols)) {
                                if ($pc['Field'] === 'id_pinjam') {
                                    $pinjam_pk = 'id_pinjam';
                                    break;
                                }
                                if ($pc['Field'] === 'id') {
                                    $pinjam_pk = 'id';
                                }
                            }
                        }

                        // Deteksi PK untuk anggota dan buku (untuk JOIN)
                        $anggota_pk = 'id';
                        $acols = mysqli_query($koneksi, "SHOW COLUMNS FROM anggota");
                        if ($acols) {
                            while ($ac = mysqli_fetch_assoc($acols)) {
                                if ($ac['Field'] === 'id_anggota') {
                                    $anggota_pk = 'id_anggota';
                                    break;
                                }
                                if ($ac['Field'] === 'id') {
                                    $anggota_pk = 'id';
                                }
                            }
                        }

                        $buku_pk = 'id_buku';
                        $bcols = mysqli_query($koneksi, "SHOW COLUMNS FROM buku");
                        if ($bcols) {
                            while ($bc = mysqli_fetch_assoc($bcols)) {
                                if ($bc['Field'] === 'id_buku') {
                                    $buku_pk = 'id_buku';
                                    break;
                                }
                                if ($bc['Field'] === 'id') {
                                    $buku_pk = 'id';
                                }
                            }
                        }

                        // Ambil hanya peminjaman yang belum dikembalikan (tanggal_kembali IS NULL)
                        $sql = "SELECT p." . $pinjam_pk . " AS pid, a.nama, b.judul, p.id_anggota, p.id_buku
                                FROM peminjaman p
                                JOIN anggota a ON p.id_anggota = a." . $anggota_pk . "
                                JOIN buku b ON p.id_buku = b." . $buku_pk . "
                                WHERE p.tanggal_kembali IS NULL";

                        $q = mysqli_query($koneksi, $sql);
                        while ($d = mysqli_fetch_array($q)) {
                            echo "<option value='$d[pid]'>$d[nama] - $d[judul]</option>";
                        }
                        ?>
                    </select>
                    <button name="kembali">Kembalikan</button>
                </form>
            </div>

            <?php
            if (isset($_POST['kembali'])) {
                $id = $_POST['pinjam'];

                // Cari primary key peminjaman lagi
                $pinjam_pk = 'id';
                $pcols = mysqli_query($koneksi, "SHOW COLUMNS FROM peminjaman");
                if ($pcols) {
                    while ($pc = mysqli_fetch_assoc($pcols)) {
                        if ($pc['Field'] === 'id_pinjam') {
                            $pinjam_pk = 'id_pinjam';
                            break;
                        }
                        if ($pc['Field'] === 'id') {
                            $pinjam_pk = 'id';
                        }
                    }
                }

                // Ambil data peminjaman
                $data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE " . $pinjam_pk . "='$id'"));
                if ($data) {
                    // Update tanggal_kembali di tabel peminjaman
                    mysqli_query($koneksi, "UPDATE peminjaman SET tanggal_kembali=CURDATE() WHERE " . $pinjam_pk . "='$id'");

                    // Update status buku menjadi Tersedia (deteksi pk buku)
                    $buku_pk_for_update = 'id_buku';
                    $bcols2 = mysqli_query($koneksi, "SHOW COLUMNS FROM buku");
                    if ($bcols2) {
                        while ($c2 = mysqli_fetch_assoc($bcols2)) {
                            if ($c2['Field'] === 'id_buku') {
                                $buku_pk_for_update = 'id_buku';
                                break;
                            }
                            if ($c2['Field'] === 'id') {
                                $buku_pk_for_update = 'id';
                            }
                        }
                    }

                    mysqli_query($koneksi, "UPDATE buku SET status='Tersedia' WHERE " . $buku_pk_for_update . "='" . $data['id_buku'] . "'");

                    // Jika tabel pengembalian ada, simpan juga riwayat pengembalian
                    $check = mysqli_query($koneksi, "SHOW TABLES LIKE 'pengembalian'");
                    if ($check && mysqli_num_rows($check) > 0) {
                        mysqli_query($koneksi, "INSERT INTO pengembalian VALUES(NULL,'$data[id_anggota]','$data[id_buku]',NOW())");
                    }

                    $success = 'Buku berhasil dikembalikan.';
                } else {
                    $error = 'Transaksi peminjaman tidak ditemukan.';
                }
            }
            ?>

            <div class="card table-card">
                <h2>Riwayat Pengembalian</h2>
                <table>
                    <tr>
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th>Tanggal</th>
                    </tr>
                    <?php
                    // Deteksi PK untuk anggota dan buku sebelum membangun JOIN
                    $anggota_pk = 'id_anggota';
                    $acols2 = mysqli_query($koneksi, "SHOW COLUMNS FROM anggota");
                    if ($acols2) {
                        while ($ac2 = mysqli_fetch_assoc($acols2)) {
                            if ($ac2['Field'] === 'id_anggota') {
                                $anggota_pk = 'id_anggota';
                                break;
                            }
                            if ($ac2['Field'] === 'id') {
                                $anggota_pk = 'id';
                            }
                        }
                    }

                    $buku_pk = 'id_buku';
                    $bcols4 = mysqli_query($koneksi, "SHOW COLUMNS FROM buku");
                    if ($bcols4) {
                        while ($bc4 = mysqli_fetch_assoc($bcols4)) {
                            if ($bc4['Field'] === 'id_buku') {
                                $buku_pk = 'id_buku';
                                break;
                            }
                            if ($bc4['Field'] === 'id') {
                                $buku_pk = 'id';
                            }
                        }
                    }

                    $sqlk = "SELECT a.nama, b.judul, k.tanggal FROM pengembalian k JOIN anggota a ON k.id_anggota = a." . $anggota_pk . " JOIN buku b ON k.id_buku = b." . $buku_pk;
                    $q = mysqli_query($koneksi, $sqlk);
                    while ($d = mysqli_fetch_array($q)) {
                        echo "<tr>
<td>$d[nama]</td>
<td>$d[judul]</td>
<td>$d[tanggal]</td>
</tr>";
                    }
                    ?>
                </table>
            </div>

        </div>
    </div>
</body>

</html>