<?php include 'koneksi.php'; ?>
<?php session_start();
if (!isset($_SESSION['admin']))
    header("Location: login.php"); ?>

<!DOCTYPE html>
<html>

<head>
    <title>Data Buku</title>
    <link rel="stylesheet" href="assets/dashboard.css">
    <script defer src="assets/animation.js"></script>
</head>

<body>

    <div class="container">

        <div class="sidebar">
            <img src="assets/logo.png" alt="Library Logo" class="sidebar-logo">
            <h2>YuPerpus</h2>
            <a href="dashboard.php">Dashboard</a>
            <a href="buku.php" class="active">Data Buku</a>
            <a href="anggota.php">Data Anggota</a>
            <a href="pinjam.php">Peminjaman</a>
            <a href="kembali.php">Pengembalian</a>
            <a href="logout.php">Logout</a>
        </div>

        <div class="content">

            <?php
            $success = '';
            $error = '';

            // Deteksi primary key kolom buku
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

            if (isset($_POST['simpan'])) {
                $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
                $penulis = mysqli_real_escape_string($koneksi, $_POST['penulis']);
                $tahun = mysqli_real_escape_string($koneksi, $_POST['tahun']);

                $insert = mysqli_query($koneksi, "INSERT INTO buku VALUES(NULL,'$judul','$penulis','$tahun','Tersedia')");
                if ($insert) {
                    $success = 'Buku berhasil ditambahkan!';
                } else {
                    $error = 'Gagal menambahkan buku: ' . mysqli_error($koneksi);
                }
            }

            if (isset($_GET['delete'])) {
                $id = intval($_GET['delete']);
                $del = mysqli_query($koneksi, "DELETE FROM buku WHERE " . $buku_pk . "='$id'");
                if ($del) {
                    $success = 'Buku berhasil dihapus!';
                } else {
                    $error = 'Gagal menghapus buku: ' . mysqli_error($koneksi);
                }
            }

            if (isset($_POST['edit'])) {
                $id = intval($_POST['id_buku']);
                $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
                $penulis = mysqli_real_escape_string($koneksi, $_POST['penulis']);
                $tahun = mysqli_real_escape_string($koneksi, $_POST['tahun']);

                $update = mysqli_query($koneksi, "UPDATE buku SET judul='$judul', penulis='$penulis', tahun='$tahun' WHERE " . $buku_pk . "='$id'");
                if ($update) {
                    $success = 'Buku berhasil diperbarui!';
                } else {
                    $error = 'Gagal memperbarui buku: ' . mysqli_error($koneksi);
                }
            }
            ?>

            <?php
            if ($success)
                echo "<div class='success'>✓ " . htmlspecialchars($success) . "</div>";
            if ($error)
                echo "<div class='error'>✗ " . htmlspecialchars($error) . "</div>";
            ?>

            <div class="card form-card">
                <h2>Tambah Buku</h2>
                <form method="post">
                    <input type="text" name="judul" placeholder="Judul Buku" autocomplete="off" required>
                    <input type="text" name="penulis" placeholder="Penulis" autocomplete="off" required>
                    <input type="number" name="tahun" placeholder="Tahun" autocomplete="off" required>
                    <button name="simpan">Simpan</button>
                </form>
            </div>

            <div class="card table-card">
                <h2>Daftar Buku</h2>
                <table>
                    <tr>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Tahun</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    <?php
                    $data = mysqli_query($koneksi, "SELECT * FROM buku");
                    while ($d = mysqli_fetch_array($data)) {
                        $id_buku = isset($d['id_buku']) ? $d['id_buku'] : (isset($d['id']) ? $d['id'] : '');
                        echo "<tr>
        <td>$d[judul]</td>
        <td>$d[penulis]</td>
        <td>$d[tahun]</td>
        <td><span class='status-" . strtolower($d['status']) . "'>$d[status]</span></td>
        <td>
            <button class='btn-edit' onclick=\"editBuku('$id_buku', '" . addslashes($d['judul']) . "', '" . addslashes($d['penulis']) . "', '$d[tahun]')\">Edit</button>
            <a href='buku.php?delete=$id_buku' onclick=\"return confirm('Yakin ingin menghapus buku ini?')\" style='color: #e74c3c; text-decoration: none; margin-left:5px;'>Hapus</a>
        </td>
    </tr>";
                    }
                    ?>
                </table>
            </div>

        </div>
    </div>

    <!-- Modal Edit Buku -->
    <div id="editModal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:white; padding:30px; border-radius:10px; width:90%; max-width:400px;">
            <h2>Edit Buku</h2>
            <form method="post" id="editForm">
                <input type="hidden" name="id_buku" id="editId">
                <input type="text" name="judul" id="editJudul" placeholder="Judul Buku" autocomplete="off" required
                    style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:5px; font-size:14px;">
                <input type="text" name="penulis" id="editPenulis" placeholder="Penulis" autocomplete="off" required
                    style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:5px; font-size:14px;">
                <input type="number" name="tahun" id="editTahun" placeholder="Tahun" autocomplete="off" required
                    style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:5px; font-size:14px;">
                <button name="edit"
                    style="width:100%; padding:10px; background:#e0b04b; color:#1b1b18; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">Simpan</button>
                <button type="button" onclick="closeEdit()"
                    style="width:100%; padding:10px; background:#999; color:white; border:none; border-radius:5px; cursor:pointer; margin-top:10px;">Batal</button>
            </form>
        </div>
    </div>

    <script>
        function editBuku(id, judul, penulis, tahun) {
            document.getElementById('editId').value = id;
            document.getElementById('editJudul').value = judul;
            document.getElementById('editPenulis').value = penulis;
            document.getElementById('editTahun').value = tahun;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEdit() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function (event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

</body>

</html>