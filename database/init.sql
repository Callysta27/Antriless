SET NAMES utf8mb4;

-- ===================== TABEL =====================

CREATE TABLE IF NOT EXISTS `user` (
  id_user      INT AUTO_INCREMENT PRIMARY KEY,
  nama         VARCHAR(100) NOT NULL,
  email        VARCHAR(100) NOT NULL UNIQUE,
  password     VARCHAR(255) NOT NULL,
  role         VARCHAR(20)  NOT NULL,
  no_telp      VARCHAR(20)  NULL,
  nim          VARCHAR(20)  NULL,
  nip          VARCHAR(30)  NULL,
  nama_kantin  VARCHAR(100) NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu (
  id_menu      INT AUTO_INCREMENT PRIMARY KEY,
  id_kantin    INT NOT NULL,
  nama_kantin  VARCHAR(100) NULL,
  nama_menu    VARCHAR(100) NOT NULL,
  harga        INT NOT NULL,
  stok         INT NOT NULL DEFAULT 0,
  kategori     VARCHAR(20)  NOT NULL,
  status       VARCHAR(20)  NOT NULL DEFAULT 'tersedia',
  foto         VARCHAR(255) NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_kantin) REFERENCES `user`(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id_order     INT AUTO_INCREMENT PRIMARY KEY,
  id_user      INT NOT NULL,
  total_harga  INT NOT NULL DEFAULT 0,
  status       VARCHAR(20) NOT NULL DEFAULT 'Diterima',
  prioritas    VARCHAR(20) NOT NULL DEFAULT 'normal',
  waktu_pesan  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_user) REFERENCES `user`(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS detail_order (
  id_detail    INT AUTO_INCREMENT PRIMARY KEY,
  id_order     INT NOT NULL,
  id_menu      INT NOT NULL,
  jumlah       INT NOT NULL,
  total        INT NOT NULL,
  FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE,
  FOREIGN KEY (id_menu)  REFERENCES menu(id_menu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment (
  id_payment        INT AUTO_INCREMENT PRIMARY KEY,
  id_order          INT NOT NULL,
  total_pembayaran  INT NOT NULL,
  metode            VARCHAR(20) NOT NULL DEFAULT 'QR',
  prioritas         VARCHAR(20) NOT NULL DEFAULT 'normal',
  status            VARCHAR(20) NOT NULL DEFAULT 'pending',
  waktu_bayar       DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice (
  id_invoice   INT AUTO_INCREMENT PRIMARY KEY,
  id_payment   INT NOT NULL,
  id_user      INT NOT NULL,
  tanggal      DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_payment) REFERENCES payment(id_payment) ON DELETE CASCADE,
  FOREIGN KEY (id_user)    REFERENCES `user`(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================== DATA CONTOH =====================

INSERT INTO `user` (nama, email, password, role, no_telp, nim, nip, nama_kantin) VALUES
('Bu Siti',        'kantin@antriless.com', 'kantin123', 'pemilik_kantin', '081200000001', NULL,        NULL,         'Kantin Bu Siti'),
('Budi Mahasiswa', 'budi@untar.ac.id',     'budi123',   'mahasiswa',      '081200000002', '825240099', NULL,         NULL),
('Pak Dosen',      'dosen@untar.ac.id',    'dosen123',  'dosen',          '081200000003', NULL,        '1987654321', NULL);

INSERT INTO menu (id_kantin, nama_kantin, nama_menu, harga, stok, kategori, status, foto) VALUES
(1, 'Kantin Bu Siti', 'Nasi Goreng Spesial', 18000, 30, 'makanan', 'tersedia', 'upload/d37bf17c96e03b533f0f4b1c9b130011.jpg'),
(1, 'Kantin Bu Siti', 'Mie Ayam Bakso',      17000, 25, 'makanan', 'tersedia', NULL),
(1, 'Kantin Bu Siti', 'Es Teh Manis',         5000, 50, 'minuman', 'tersedia', 'upload/es teh manis.jpg'),
(1, 'Kantin Bu Siti', 'Kentang Goreng',      12000, 20, 'camilan', 'tersedia', NULL),
(1, 'Kantin Bu Siti', 'Pudding Coklat',       8000, 15, 'dessert', 'tersedia', NULL);