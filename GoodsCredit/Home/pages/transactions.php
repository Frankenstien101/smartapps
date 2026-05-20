<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Transactions</title>
  <style>
    h3 {
      margin-top: 30px;
      margin-bottom: 10px;
      padding-left: 15px;
      font-weight: bold;
    }
    .section-container {
      padding-left: 15px;
      padding-right: 15px;
    }
    /* Card styles */
    .card-custom {
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      width: 180px;
      height: 210px;
      margin: 10px;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 10px;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .card-custom:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 20px rgba(0,0,0,0.2);
    }
    .card-icon {
      width: 100px;
      height: 130px;
      object-fit: contain;
    }
    .card-title {
      margin-top: 1px;
      font-weight: bold;
      font-size: 1rem;
      text-align: center;
    }
    .card-text {
      margin-top: 1px;
      font-size: 0.75rem;
      color: #555;
      text-align: center;
    }

    /* Adjust margins for mobile to align the first card */
    @media (max-width: 767.98px) {
      /* Remove side margins for first child to align precisely */
      .row-cards > .col:first-child {
        padding-left: 0;
      }
    }
  </style>
  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" 
    integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous" />
</head>
<body>

  <h3>TRANSACTION</h3>
  <div class="section-container">
    <div class="row row-cards d-flex flex-wrap">
      <!-- Wrap each card in a col -->
      <div class="col-auto px-2 first-card">
        <a href="Home.php?page=CreditCreation" class="card-link">
          <div class="card-custom">
            <img src="\BlueBook\Home\img\transactions\receipt.jpg" class="card-icon" alt="Upload Orders Icon"/>
            <div class="card-title">CREDIT CREATION</div>
            <div class="card-text">Track money coming into your business.</div>
          </div>
        </a>
      </div>
      <div class="col-auto px-2">
        <a href="#" class="card-link">
          <div class="card-custom">
            <img src="\BlueBook\Home\img\transactions\payments.jpg" class="card-icon" alt="Order View Icon"/>
            <div class="card-title">CHARGE MANAGEMENT</div>
            <div class="card-text">Log outgoing payments and bills.</div>
          </div>
        </a>
      </div>
    </div>
  </div>

  <h3>INVENTORY</h3>
  <div class="section-container">
    <div class="row row-cards d-flex flex-wrap">
      <div class="col-auto px-2">
        <a href="#" class="card-link">
          <div class="card-custom">
            <img src="\BlueBook\Home\img\transactions\chart.jpg" class="card-icon" alt="Delivery Plan Icon"/>
            <div class="card-title">STOCK MANAGEMENT</div>
            <div class="card-text">List and organize all accounts used in bookkeeping.</div>
          </div>
        </a>
      </div>
    </div>
  </div>

   <h3>PAYMENTS</h3>
  <div class="section-container">
    <div class="row row-cards d-flex flex-wrap">
      <!-- Wrap each card in a col -->
      <div class="col-auto px-2 first-card">
        <a href="#" class="card-link">
          <div class="card-custom">
            <img src="\Dash\Home\img\transactions\upload.png" class="card-icon" alt="Upload Orders Icon"/>
            <div class="card-title">CHARGE PAYMENT</div>
            <div class="card-text">Manage items you sell or purchase.</div>
          </div>
        </a>
      </div>
      <div class="col-auto px-2">
        <a href="#" class="card-link">
          <div class="card-custom">
            <img src="\Dash\Home\img\transactions\vieworders.jpg" class="card-icon" alt="Order View Icon"/>
            <div class="card-title">CASH PAYMENT</div>
            <div class="card-text">Record goods bought from suppliers.</div>
          </div>
        </a>
      </div>
    </div>
  </div>

   <h3>ADJUSTMENTS</h3>
  <div class="section-container">
    <div class="row row-cards d-flex flex-wrap">
      <!-- Wrap each card in a col -->
      <div class="col-auto px-2 first-card">
        <a href="#" class="card-link">
          <div class="card-custom">
            <img src="\BlueBook\Home\img\transactions\invoice.jpg" class="card-icon" alt="Upload Orders Icon"/>
            <div class="card-title">VOID TRANSACTION</div>
            <div class="card-text">Generate a new invoice.</div>
          </div>
        </a>
    </div>

</body>
</html>