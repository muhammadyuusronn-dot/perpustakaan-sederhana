<?php
session_start();
if (!isset($_SESSION['admin']))
    header("Location: login.php");
include 'koneksi.php';
?>
<!DOCTYPE html>
<html>

<head>
    <title>Data Anggota</title>
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
            <a href="anggota.php" class="active">Data Anggota</a>
            <a href="pinjam.php">Peminjaman</a>
            <a href="kembali.php">Pengembalian</a>
            <a href="logout.php">Logout</a>
        </div>

        <div class="content normal-bg">

            <?php
            $success = '';
            $error = '';

            // Deteksi primary key kolom anggota
            $anggota_pk = 'id_anggota';
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

            if (isset($_POST['simpan'])) {
                $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
                $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
                $telp = mysqli_real_escape_string($koneksi, $_POST['telp']);

                $insert = mysqli_query($koneksi, "INSERT INTO anggota VALUES(NULL,'$nama','$alamat','$telp')");
                if ($insert) {
                    $success = 'Anggota berhasil ditambahkan!';
                } else {
                    $error = 'Gagal menambahkan anggota: ' . mysqli_error($koneksi);
                }
            }

            if (isset($_GET['delete'])) {
                $id = intval($_GET['delete']);
                $del = mysqli_query($koneksi, "DELETE FROM anggota WHERE " . $anggota_pk . "='$id'");
                if ($del) {
                    $success = 'Anggota berhasil dihapus!';
                } else {
                    $error = 'Gagal menghapus anggota: ' . mysqli_error($koneksi);
                }
            }

            if (isset($_POST['edit'])) {
                $id = intval($_POST['id_anggota']);
                $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
                $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
                $telp = mysqli_real_escape_string($koneksi, $_POST['telp']);

                $update = mysqli_query($koneksi, "UPDATE anggota SET nama='$nama', alamat='$alamat', telp='$telp' WHERE " . $anggota_pk . "='$id'");
                if ($update) {
                    $success = 'Anggota berhasil diperbarui!';
                } else {
                    $error = 'Gagal memperbarui anggota: ' . mysqli_error($koneksi);
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
                <h2>Tambah Anggota</h2>
                <form method="post">
                    <input type="text" name="nama" placeholder="Nama Anggota" autocomplete="off" required>
                    <input type="text" name="alamat" placeholder="Alamat" autocomplete="off" required>
                    <input type="text" name="telp" placeholder="No Telepon" autocomplete="off" required>
                    <button name="simpan">Simpan</button>
                </form>
            </div>

            <div class="card table-card">
                <h2>Daftar Anggota</h2>
                <table>
                    <tr>
                        <th>Nama</th>
                        <th>Alamat</th>
                        <th>Telepon</th>
                        <th>Aksi</th>
                    </tr>
                    <?php
                    $q = mysqli_query($koneksi, "SELECT * FROM anggota");
                    while ($d = mysqli_fetch_array($q)) {
                        $id_anggota = isset($d['id_anggota']) ? $d['id_anggota'] : (isset($d['id']) ? $d['id'] : '');
                        echo "<tr>
<td>$d[nama]</td>
<td>$d[alamat]</td>
<td>$d[telp]</td>
<td>
    <button class='btn-edit' onclick=\"editAnggota('$id_anggota', '" . addslashes($d['nama']) . "', '" . addslashes($d['alamat']) . "', '$d[telp]')\">Edit</button>
    <a href='anggota.php?delete=$id_anggota' onclick=\"return confirm('Yakin ingin menghapus anggota ini?')\" style='color: #e74c3c; text-decoration: none; margin-left:5px;'>Hapus</a>
</td>
</tr>";
                    }
                    ?>
                </table>
            </div>

        </div>
    </div>

    <!-- Modal Edit Anggota -->
    <div id="editModal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:white; padding:30px; border-radius:10px; width:90%; max-width:400px;">
            <h2>Edit Anggota</h2>
            <form method="post" id="editForm">
                <input type="hidden" name="id_anggota" id="editId">
                <input type="text" name="nama" id="editNama" placeholder="Nama Anggota" autocomplete="off" required
                    style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:5px; font-size:14px; box-sizing:border-box;">
                <input type="text" name="alamat" id="editAlamat" placeholder="Alamat" autocomplete="off" required
                    style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:5px; font-size:14px; box-sizing:border-box;">
                <input type="text" name="telp" id="editTelp" placeholder="No Telepon" autocomplete="off" required
                    style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:5px; font-size:14px; box-sizing:border-box;">
                <button name="edit"
                    style="width:100%; padding:10px; background:#e0b04b; color:#1b1b18; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">Simpan</button>
                <button type="button" onclick="closeEdit()"
                    style="width:100%; padding:10px; background:#999; color:white; border:none; border-radius:5px; cursor:pointer; margin-top:10px;">Batal</button>
            </form>
        </div>
    </div>

    <script>
        function editAnggota(id, nama, alamat, telp) {
            document.getElementById('editId').value = id;
            document.getElementById('editNama').value = nama;
            document.getElementById('editAlamat').value = alamat;
            document.getElementById('editTelp').value = telp;
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