<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Membership Card | Citizens Movement for Change</title>
  <style>
    @media print {
      body {
        margin: 0;
        padding: 0;
      }

      #printBtn {
        display: none;
      }

    }

    body {
      font-family: Arial, sans-serif;
      background-color: #f3f3f3;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      gap: 40px;
      position: relative;
    }

    .card {
      width: 300px;
      height: 480px;
      background-color: white;
      border: 1px solid #ccc;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      padding: 20px;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
    }

    .card.front::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 0;
      height: 0;
      border-top: 50px solid red;
      border-right: 50px solid transparent;
      z-index: 1;
    }

    .card.front::after {
      content: "";
      position: absolute;
      bottom: 0;
      right: 0;
      width: 0;
      height: 0;
      border-bottom: 50px solid #0047ab;
      border-left: 50px solid transparent;
      z-index: 1;
    }

    .card.front .corner-circle {
      position: absolute;
      width: 80px;
      height: 40px;
      background-color: white;
      border-radius: 50%;
      /* elliptical shape */
      z-index: 2;
      transform: rotate(135deg);
    }

    .card.front .top-left-circle {
      top: 10px;
      left: -10px;
    }

    .card.front .bottom-right-circle {
      bottom: 10px;
      right: -10px;
    }

    .header {
      display: flex;
      align-items: center;
      gap: 10px;
      justify-content: center;
    }

    .header-logo {
      width: 65px;
      height: 65px;
    }

    .header-text {
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .header-title {
      font-size: 14px;
      font-weight: bold;
      color: #0047ab;
      line-height: 1.1;
    }

    .header-tagline {
      font-size: 9px;
      font-style: italic;
      color: #333;
      margin-top: 2px;
    }

    .profile {
      text-align: center;
      margin-top: 10px;
    }

    .profile img {
      width: 90px;
      height: 90px;
      object-fit: cover;
      border-radius: 5px;
    }

    .name {
      font-weight: bold;
      font-size: 18px;
      color: #0047ab;
      margin-top: 10px;
    }

    .role {
      font-size: 13px;
      color: #555;
      margin-bottom: 10px;
    }

    .info-section {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      font-size: 11px;
      margin-top: 10px;
      position: relative;
    }

    .info-left,
    .info-right {
      width: 48%;
    }

    .info-left {
      text-align: right;
      padding-right: 10px;
    }

    .info-right {
      padding-left: 10px;
    }

    .info-left div,
    .info-right div {
      margin-bottom: 8px;
    }

    .label {
      font-weight: bold;
      color: #0047ab;
    }

    .value {
      color: #000;
    }

    .info-separator {
      position: absolute;
      top: 0;
      bottom: 0;
      left: 50%;
      width: 1px;
      border-left: 1px dotted #aaa;
    }

    .qr {
      text-align: left;
      margin-top: 5px;
    }

    .qr img {
      width: 70px;
      height: 70px;
    }

    .footer {
      font-size: 10px;
      opacity: 0.6;
      text-align: left;
    }

    .seal {
      width: 200px;
      height: 200px;
      object-fit: contain;
      margin: 20px auto;
    }

    .back-title {
      text-align: center;
      font-size: 14px;
      font-weight: bold;
      background-color: #0047ab;
      color: white;
      padding: 10px;
      margin: 10px 0 5px;
      -webkit-print-color-adjust: exact;
    }

    .notice {
      font-size: 12px;
      color: #333;
      text-align: center;
      padding: 0 10px;
    }

    .back-footer {
      background-color: #0047ab;
      padding: 10px;
      border-radius: 6px;
      text-align: center;
      -webkit-print-color-adjust: exact;
    }

    .footer-header {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .footer-logo {
      width: 50px;
      height: 50px;
    }

    .footer-text {
      display: flex;
      flex-direction: column;
      justify-content: center;
      text-align: left;
      color: white;
    }

    .footer-title {
      font-size: 12px;
      font-weight: bold;
      line-height: 1.1;
    }

    .footer-tagline {
      font-size: 9px;
      font-style: italic;
      margin-top: 2px;
    }

    #printBtn {
      position: absolute;
      top: 10px;
      right: 10px;
      padding: 5px 10px;
      background-color: #0047ab;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
  </style>
</head>

<body>

  <!-- FRONT SIDE -->
  <div class="card front">
    <div class="corner-circle top-left-circle"></div>
    <div class="corner-circle bottom-right-circle"></div>

    <div class="header">
      <img src="data:image/png;base64,{{ $logoBase64 }}" alt="CMC Logo" class="header-logo" />
      <div class="header-text">
        <div class="header-title">CITIZENS<br> MOVEMENT<br> FOR CHANGE</div>
        <div class="header-tagline">For the People, By the People</div>
      </div>
    </div>

    <div class="profile">
      <img src="data:image/jpeg;base64,{{ $avatarBase64 }}" alt="Member Photo" />
      <div class="name">{{ $user->name ?? 'N/A' }}</div>
      <div class="role">{{ $user->profile->type ?? 'N/A' }}</div>
    </div>

    <div class="info-section">
      <div class="info-left">
        <div>
          <div class="label">D.O.B</div>
          <div class="value">{{ \Carbon\Carbon::parse($user->dob)->format('d/m/Y') }}</div>
        </div>
        <div>
          <div class="label">Date Issued</div>
          <div class="value">{{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y') }}</div>
        </div>
        <div>
          <div class="label">ID No.</div>
          <div class="value">{{ $user->slug }}</div>
        </div>
      </div>

      <div class="info-separator"></div>

      <div class="info-right">
        <div>
          <div class="label">Gender</div>
          <div class="value">{{ ucfirst($user->profile->gender) }}</div>
        </div>
        <div class="qr">
          {{ $qrCode }}
        </div>
      </div>
    </div>

    <div class="footer">ID No. {{ $user->slug }}</div>
  </div>

  <!-- BACK SIDE -->
  <div class="card">
    <div class="org-name" style="text-align: center; font-size: 13px;">Republic of Liberia</div>
    <div class="back-title">MEMBERSHIP ID CARD</div>

    <img src="data:image/png;base64,{{ $backLogoBase64 }}" alt="Liberia Seal" class="seal" />

    <div class="notice">
      If found, please return to the nearest police station or any Citizens Movement for Change (CMC) office.
    </div>

    <div class="back-footer">
      <div class="footer-header">
        <img src="data:image/png;base64,{{ $whitelogoBase64 }}" alt="CMC Logo" class="footer-logo" />
        <div class="footer-text">
          <div class="footer-title">CITIZENS<br> MOVEMENT<br> FOR CHANGE</div>
          <div class="footer-tagline">For the People, By the People</div>
        </div>
      </div>
    </div>
  </div>
  <button type="button" id="printBtn">Print</button>
</body>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("printBtn").addEventListener("click", function() {
      window.print();
    });
  });
</script>

</html>