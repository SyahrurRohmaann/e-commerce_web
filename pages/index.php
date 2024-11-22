  <?php
$pageTitle = 'Home';
include '../templates/header.php';
require_once '../php/config.php';

// Ambil 4 produk terbaru dari database
try {
    $db = new config();
    $stmt = $db->prepare("
        SELECT p.*, k.nama_kategori 
        FROM produk p 
        JOIN kategori k ON p.id_kategori = k.id_kategori 
        ORDER BY p.id_produk DESC 
        LIMIT 4
    ");
    $stmt->execute();
    $newProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    $newProducts = [];
}
?>

    
  <div class="slider-awal">
    <div class="image-slider">
      <section class="slider__content">
        <button type="button" class="slider-control--button prev-button">
          <svg
            width="16"
            height="16"
            fill="currentColor"
            class="icon arrow-left-circle"
            viewBox="0 0 16 16"
          >
            <path
              fill-rule="evenodd"
              d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5z"
            />
          </svg>
        </button>
        <main class="image-display"></main>
        <button type="button" class="slider-control--button next-button">
          <svg
            width="16"
            height="16"
            fill="currentColor"
            class="icon arrow-right-circle"
            viewBox="0 0 16 16"
          >
            <path
              fill-rule="evenodd"
              d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0M4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5z"
            />
          </svg>
        </button>
      </section>
      <nav class="slider-navigation">
        <button class="nav-button" aria-selected="true">
          <img
            class="thumbnail"
            src="../assets/image/slider-1.jpg"
            alt="Thumbnail 1"
          />
        </button>
        <button class="nav-button" aria-selected="false">
          <img
            class="thumbnail"
            src="../assets/image/slider-2.jpg"
            alt="Thumbnail 2"
          />
        </button>
        <button class="nav-button" aria-selected="false">
          <img
            class="thumbnail"
            src="../assets/image/slider-3.jpg"
            alt="Thumbnail 3"
          />
        </button>
      </nav>
    </div>
    </div>
    <script>
      class Slider {
    constructor(slider) {
        this.slider = slider;
        this.display = slider.querySelector(".image-display");
        this.navButtons = Array.from(slider.querySelectorAll(".nav-button"));
        this.prevButton = slider.querySelector(".prev-button");
        this.nextButton = slider.querySelector(".next-button");
        this.sliderNavigation = slider.querySelector(".slider-navigation");
        this.currentSlideIndex = 0;
        this.preloadedImages = {};

        this.initialize();
    }

    initialize() {
        this.setupSlider();
        this.preloadImages();
        this.eventListeners();
    }

    setupSlider() {
        this.showSlide(this.currentSlideIndex);
    }

    showSlide(index) {
        this.currentSlideIndex = index;
        const navButtonImg = this.navButtons[
            this.currentSlideIndex
        ].querySelector("img");
        if (navButtonImg) {
            const imgClone = navButtonImg.cloneNode();
            this.display.replaceChildren(imgClone);
        }
        this.updateNavButtons();
    }

    updateNavButtons() {
        this.navButtons.forEach((button, buttonIndex) => {
            const isSelected = buttonIndex === this.currentSlideIndex;
            button.setAttribute("aria-selected", isSelected);
            if (isSelected) button.focus();
        });
    }

    preloadImages() {
        this.navButtons.forEach((button) => {
            const imgElement = button.querySelector("img");
            if (imgElement) {
                const imgSrc = imgElement.src;
                if (!this.preloadedImages[imgSrc]) {
                    this.preloadedImages[imgSrc] = new Image();
                    this.preloadedImages[imgSrc].src = imgSrc;
                }
            }
        });
    }

    eventListeners() {
        document.addEventListener("keydown", (event) => {
            this.handleAction(event.key);
        });

        this.sliderNavigation.addEventListener("click", (event) => {
            const targetButton = event.target.closest(".nav-button");
            const index = targetButton
                ? this.navButtons.indexOf(targetButton)
                : -1;
            if (index !== -1) {
                this.showSlide(index);
            }
        });

        this.prevButton.addEventListener("click", () =>
            this.handleAction("prev")
        );
        this.nextButton.addEventListener("click", () =>
            this.handleAction("next")
        );
    }

    handleAction(action) {
        if (action === "Home") {
            this.currentSlideIndex = 0;
        } else if (action === "End") {
            this.currentSlideIndex = this.navButtons.length - 1;
        } else if (action === "ArrowRight" || action === "next") {
            this.currentSlideIndex =
                (this.currentSlideIndex + 1) % this.navButtons.length;
        } else if (action === "ArrowLeft" || action === "prev") {
            this.currentSlideIndex =
                (this.currentSlideIndex - 1 + this.navButtons.length) %
                this.navButtons.length;
        }

        this.showSlide(this.currentSlideIndex);
    }
}

const ImageSlider = new Slider(document.querySelector(".image-slider"));

    </script>

    <main>
      <div class="new-arrival">
        <h2>NEW ARRIVAL</h2>
        <div class="products">
          <?php foreach($newProducts as $product): ?>
                <div class="product">
                    <img src="../uploads/<?= htmlspecialchars($product['gambar']) ?>" 
                         alt="<?= htmlspecialchars($product['nama']) ?>" 
                         onerror="this.src='../assets/image/product-1.jpg'"/>
                    <p class="nama-product"><?= htmlspecialchars($product['nama']) ?></p>
                    <p>IDR <?= number_format($product['harga'], 0, ',', '.') ?></p>
                    <?php if($product['stock'] > 0): ?>
                        <p class="stock">Stock: <?= $product['stock'] ?></p>
                    <?php else: ?>
                        <p class="out-of-stock">Out of Stock</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        </br><div class="view-all">
          <button class="new-arrival-btn">View All</button>
        </div>
      </div>

      <div class="top-selling">
        <h2>TOP SELLING</h2>
        <div class="products">
          <?php
        try {
            // Query untuk mengambil produk terlaris berdasarkan jumlah transaksi
            $stmt = $db->prepare("
                SELECT p.*, k.nama_kategori, COUNT(dt.id_produk) as total_sold 
                FROM produk p 
                JOIN kategori k ON p.id_kategori = k.id_kategori
                LEFT JOIN detail_transaksi dt ON p.id_produk = dt.id_produk 
                GROUP BY p.id_produk 
                ORDER BY total_sold DESC, p.id_produk DESC
                LIMIT 4
            ");
            $stmt->execute();
            $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($topProducts as $product): ?>
                <div class="product">
                    <img src="../uploads/<?= htmlspecialchars($product['gambar']) ?>" 
                         alt="<?= htmlspecialchars($product['nama']) ?>" 
                         onerror="this.src='../assets/image/product-1.jpg'"/>
                    <p class="nama-product"><?= htmlspecialchars($product['nama']) ?></p>
                    <p>IDR <?= number_format($product['harga'], 0, ',', '.') ?></p>
                    <?php if($product['stock'] > 0): ?>
                        <p class="stock"><?= $product['stock'] ?></p>
                    <?php else: ?>
                        <p class="out-of-stock">Out of Stock</p>
                    <?php endif; ?>
                </div>
            <?php endforeach;
            } catch(PDOException $e) {
              echo "Error: " . $e->getMessage();
          }
          ?>
        </div>
        </br><div class="view-all">
          <button class="top-selling-btn">View All</button>
        </div>
      </div>
    </main>

  <?php 

include '../templates/footer.php'; 
?>