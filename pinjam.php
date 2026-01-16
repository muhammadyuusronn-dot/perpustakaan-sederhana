<?php
session_start();
if (!isset($_SESSION['admin']))
    header("Location: login.php");
include 'koneksi.php';
?>
<!DOCTYPE html>
<html>

<head>
    <title>Peminjaman</title>
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
            <a href="pinjam.php" class="active">Peminjaman</a>
            <a href="kembali.php">Pengembalian</a>
            <a href="logout.php">Logout</a>
        </div>

        <div class="content normal-bg">

            <div class="card form-card">
                <h2>Transaksi Peminjaman</h2>
                <form method="post" autocomplete="off">
                    <select name="anggota" required>
                        <option value="">Pilih Anggota</option>
                        <?php
                        $a = mysqli_query($koneksi, "SELECT * FROM anggota");
                        while ($x = mysqli_fetch_array($a)) {
                            // Tabel anggota menggunakan kolom 'id' sebagai primary key
                            $anggota_id = isset($x['id_anggota']) ? $x['id_anggota'] : $x['id'];
                            echo "<option value='$anggota_id'>$x[nama]</option>";
                        }
                        ?>
                    </select>

                    <select name="buku" required>
                        <option value="">Pilih Buku</option>
                        <?php
                        // Deteksi primary key tabel buku (id_buku atau id)
                        $buku_pk = 'id_buku';
                        $bcols = mysqli_query($koneksi, "SHOW COLUMNS FROM buku");
                        if ($bcols) {
                            while ($c = mysqli_fetch_assoc($bcols)) {
                                if ($c['Field'] === 'id_buku') {
                                    $buku_pk = 'id_buku';
                                    break;
                                }
                                if ($c['Field'] === 'id') {
                                    $buku_pk = 'id';
                                }
                            }
                        }

                        $b = mysqli_query($koneksi, "SELECT * FROM buku WHERE status='Tersedia'");
                        while ($y = mysqli_fetch_array($b)) {
                            $b_id = isset($y['id_buku']) ? $y['id_buku'] : (isset($y['id']) ? $y['id'] : '');
                            echo "<option value='$b_id'>$y[judul]</option>";
                        }
                        ?>
                    </select>

                    <button name="pinjam">Pinjam</button>
                </form>
            </div>

            <?php
            $success = '';
            $error = '';

            if (isset($_POST['pinjam'])) {

                $id_anggota = $_POST['anggota'];
                $id_buku = $_POST['buku'];

                $insert = mysqli_query($koneksi, "INSERT INTO peminjaman (id_buku, id_anggota, tanggal_pinjam, tanggal_kembali) VALUES ('$id_buku', '$id_anggota', CURDATE(), NULL)");

                if ($insert) {
                    // Update buku status menggunakan primary key yang sesuai
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

                    $update = mysqli_query($koneksi, "UPDATE buku SET status='Dipinjam' WHERE " . $buku_pk_for_update . "='$id_buku'");
                    $success = 'Buku berhasil dipinjam.';
                } else {
                    $error = 'Gagal menyimpan data peminjaman.';
                }
            }
            ?>

            <?php
            if ($success)
                echo "<div class='success'>✓ " . htmlspecialchars($success) . "</div>";
            if ($error)
                echo "<div class='error'>✗ " . htmlspecialchars($error) . "</div>";
            ?>

            <div class="card table-card">
                <h2>Data Peminjaman</h2>
                <table>
                    <tr>
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th>Tanggal</th>
                    </tr>
                    <?php
                    // Sesuaikan join dengan struktur DB: anggota mungkin memakai kolom 'id'
                    // Deteksi nama kolom primary key pada tabel anggota (id_anggota atau id)
                    $anggota_pk = 'id';
                    $cols = mysqli_query($koneksi, "SHOW COLUMNS FROM anggota");
                    if ($cols) {
                        while ($col = mysqli_fetch_assoc($cols)) {
                            if ($col['Field'] === 'id_anggota') {
                                $anggota_pk = 'id_anggota';
                                break;
                            }
                            if ($col['Field'] === 'id') {
                                $anggota_pk = 'id';
                            }
                        }
                    }

                    // Deteksi primary key tabel buku juga untuk JOIN
                    $buku_pk = 'id_buku';
                    $bcols3 = mysqli_query($koneksi, "SHOW COLUMNS FROM buku");
                    if ($bcols3) {
                        while ($c3 = mysqli_fetch_assoc($bcols3)) {
                            if ($c3['Field'] === 'id_buku') {
                                $buku_pk = 'id_buku';
                                break;
                            }
                            if ($c3['Field'] === 'id') {
                                $buku_pk = 'id';
                            }
                        }
                    }

                    $sql = "SELECT a.nama, b.judul, p.tanggal_pinjam AS tanggal
                            FROM peminjaman p
                            JOIN anggota a ON p.id_anggota = a." . $anggota_pk . "
                            JOIN buku b ON p.id_buku = b." . $buku_pk;

                    $q = mysqli_query($koneksi, $sql);
                    if ($q) {
                        while ($d = mysqli_fetch_array($q)) {
                            echo "<tr>
<td>$d[nama]</td>
<td>$d[judul]</td>
<td>$d[tanggal]</td>
</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>Tidak dapat mengambil data peminjaman.</td></tr>";
                    }
                    ?>
                </table>
            </div>

        </div>
    </div>
</body>

</html>