<?php
// track.php
$orderId = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta
    name="viewport"
    content="width=device-width, initial-scale=1, maximum-scale=1" />
  <title>Live Order Tracker — <?php echo htmlspecialchars($orderId ?: 'Missing order_id'); ?></title>

  <style>
    :root {
      --ink: #111827;
      --muted: #6b7280;
      --card: #ffffff;
      --stroke: #e5e7eb;
    }

    * {
      box-sizing: border-box
    }

    html,
    body {
      height: 100%
    }

    body {
      margin: 0;
      background: #ffffff;
      color: var(--ink);
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial;
    }

    .wrap {
      max-width: 1100px;
      margin: 24px auto;
      padding: 0 16px
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 16px
    }

    .title {
      font-weight: 700;
      font-size: 22px;
      letter-spacing: .2px
    }

    .pill {
      background: #f8fafc;
      border: 1px solid var(--stroke);
      padding: 8px 12px;
      border-radius: 999px;
      color: var(--muted);
      font-size: 13px
    }

    .grid {
      display: grid;
      grid-template-columns: 1.2fr .8fr;
      gap: 16px
    }

    #map {
      height: 70vh;
      border-radius: 16px;
      border: 1px solid var(--stroke);
      overflow: hidden
    }

    .card {
      background: var(--card);
      border: 1px solid var(--stroke);
      border-radius: 16px;
      padding: 14px 14px 2px 14px
    }

    .card h3 {
      margin: 4px 0 6px 0;
      font-size: 15px;
      color: var(--muted);
      font-weight: 600
    }

    .row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin: 8px 0 12px
    }

    .k {
      color: var(--muted);
      font-size: 13px
    }

    .v {
      font-size: 14px
    }

    .muted {
      color: var(--muted)
    }

    @media (max-width: 900px) {
      .grid {
        grid-template-columns: 1fr
      }

      #map {
        height: 55vh
      }
    }

    /* Advanced Marker icon sizing */
    .marker-img {
      width: 36px;
      height: 36px;
      display: block
    }

    .marker-rot {
      transform-origin: 50% 50%;
      will-change: transform
    }

    /* driver rotates */
  </style>
</head>

<body>
  <div class="wrap">
    <div class="topbar">
      <div class="title">Live Order Tracker</div>
      <div class="pill">Order ID: <strong><?php echo htmlspecialchars($orderId ?: '—'); ?></strong></div>
    </div>

    <?php if (!$orderId): ?>
      <div class="card">
        <p style="color:#ef4444;font-weight:600">Missing <code>order_id</code> in URL. Example: <code>?order_id=2</code></p>
      </div>
    <?php else: ?>
      <div class="grid">
        <div id="map"></div>

        <div class="card" id="dataPanel">
          <h3>Details</h3>

          <div class="row">
            <div class="k">Status</div>
            <div class="v" id="status">—</div>
          </div>
          <div class="row">
            <div class="k">Updated at</div>
            <div class="v" id="updatedAt" title="Server timestamp">—</div>
          </div>
          <hr style="border-color:var(--stroke);opacity:.6">

          <h3>Pickup</h3>
          <div class="row">
            <div class="k">Location</div>
            <div class="v" id="pickupLoc">—</div>
          </div>
          <div class="row">
            <div class="k">Lat, Lng</div>
            <div class="v" id="pickupLL">—</div>
          </div>
          <div class="row">
            <div class="k">Open</div>
            <div class="v"><a id="pickupLink" href="#" target="_blank" rel="noopener">Maps</a></div>
          </div>

          <h3>Drop</h3>
          <div class="row">
            <div class="k">Location</div>
            <div class="v" id="dropLoc">—</div>
          </div>
          <div class="row">
            <div class="k">Lat, Lng</div>
            <div class="v" id="dropLL">—</div>
          </div>
          <div class="row">
            <div class="k">Open</div>
            <div class="v"><a id="dropLink" href="#" target="_blank" rel="noopener">Maps</a></div>
          </div>

          <h3>Driver</h3>
          <div class="row">
            <div class="k">Driver ID</div>
            <div class="v" id="driverId">—</div>
          </div>
          <div class="row">
            <div class="k">Lat, Lng</div>
            <div class="v" id="driverLL">—</div>
          </div>
          <div class="row">
            <div class="k">Speed</div>
            <div class="v" id="driverSpeed">—</div>
          </div>
          <div class="row">
            <div class="k">Heading</div>
            <div class="v" id="driverHeading">—</div>
          </div>
          <div class="row">
            <div class="k">Open</div>
            <div class="v"><a id="driverLink" href="#" target="_blank" rel="noopener">Maps</a></div>
          </div>

          <p class="muted" style="margin-top:10px">Panel updates in real-time.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($orderId): ?>
    <!-- Firebase (module): initialize and expose Firestore + order id to window -->
    <script type="module">
      import {
        initializeApp
      } from "https://www.gstatic.com/firebasejs/10.12.4/firebase-app.js";
      import {
        getFirestore,
        doc,
        onSnapshot
      } from "https://www.gstatic.com/firebasejs/10.12.4/firebase-firestore.js";

      const firebaseConfig = {
        apiKey: "AIzaSyDgizWVhrSn7HoWkzQWIbfWdGPHHtzi87c",
        authDomain: "test-696e0.firebaseapp.com",
        projectId: "test-696e0",
        storageBucket: "test-696e0.firebasestorage.app",
        messagingSenderId: "313160059021",
        appId: "1:313160059021:web:dcd3e373c0a6336593fac4",
        measurementId: "G-ZVQYXW3W3W"
      };

      const app = initializeApp(firebaseConfig);
      const db = getFirestore(app);

      window.__firestore = {
        db,
        doc,
        onSnapshot
      };
      window.__ORDER_ID = <?php echo json_encode($orderId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>

    <!-- Non-module: define global initMap BEFORE loading Google Maps -->
    <script>
      function normStatus(s) {
        return (typeof s === 'string' ? s.trim().toLowerCase() : '');
      }

      function gLL(lat, lng) {
        return new google.maps.LatLng(lat, lng);
      }
      const fmt = v => Number.isFinite(v) ? v.toFixed(6) : '—';
      const kmh = mps => Number.isFinite(mps) ? (mps * 3.6).toFixed(1) + ' km/h' : '—';

      function bearing(a, b) {
        const toRad = d => d * Math.PI / 180,
          toDeg = r => r * 180 / Math.PI;
        const φ1 = toRad(a.lat()),
          φ2 = toRad(b.lat());
        const Δλ = toRad(b.lng() - a.lng());
        const y = Math.sin(Δλ) * Math.cos(φ2);
        const x = Math.cos(φ1) * Math.cos(φ2) * Math.cos(Δλ) - Math.sin(φ1) * Math.sin(φ2);
        const θ = Math.atan2(y, x);
        return (toDeg(θ) + 360) % 360;
      }

      function selectLeg(status, p, q, drv) {
        const ns = normStatus(status);
        if (ns === 'accepted') {
          if (drv && p) return {
            origin: drv,
            destination: p,
            label: 'driver→pickup'
          };
          if (p && q) return {
            origin: p,
            destination: q,
            label: 'pickup→drop'
          };
          if (drv && q) return {
            origin: drv,
            destination: q,
            label: 'driver→drop'
          };
        }
        if (ns === 'collected') {
          if (drv && q) return {
            origin: drv,
            destination: q,
            label: 'driver→drop'
          };
          if (p && q) return {
            origin: p,
            destination: q,
            label: 'pickup→drop'
          };
          if (drv && p) return {
            origin: drv,
            destination: p,
            label: 'driver→pickup'
          };
        }
        if (p && q) return {
          origin: p,
          destination: q,
          label: 'pickup→drop'
        };
        if (drv && p) return {
          origin: drv,
          destination: p,
          label: 'driver→pickup'
        };
        if (drv && q) return {
          origin: drv,
          destination: q,
          label: 'driver→drop'
        };
        return null;
      }

      function locText(loc) {
        if (!loc) return '—';
        if (typeof loc === 'string') return loc.trim() || '—';
        // If it’s an object, prefer common fields
        const parts = [loc.name, loc.address, loc.description, loc.line1, loc.line2]
          .filter(v => typeof v === 'string' && v.trim().length);
        return parts.length ? parts.join(', ') : '—';
      }

      window.initMap = function() {
        const {
          db,
          doc,
          onSnapshot
        } = window.__firestore;
        const ORDER_ID = window.__ORDER_ID;

        // DOM
        const $status = document.getElementById('status');
        const $updatedAt = document.getElementById('updatedAt');
        const $pickupLL = document.getElementById('pickupLL');
        const $pickupLink = document.getElementById('pickupLink');
        const $pickupLoc = document.getElementById('pickupLoc');
        const $dropLoc = document.getElementById('dropLoc');
        const $dropLL = document.getElementById('dropLL');
        const $dropLink = document.getElementById('dropLink');
        const $driverId = document.getElementById('driverId');
        const $driverLL = document.getElementById('driverLL');
        const $driverSpeed = document.getElementById('driverSpeed');
        const $driverHeading = document.getElementById('driverHeading');
        const $driverLink = document.getElementById('driverLink');

        // Map (vector) — mapId REQUIRED for Advanced Markers
        const map = new google.maps.Map(document.getElementById("map"), {
          center: {
            lat: 20,
            lng: 0
          },
          zoom: 2,
          heading: 0,
          tilt: 0,
          mapId: "a6b834b4a68246a8b14d4aff" // ← paste your Map ID here
        });

        // Directions
        const directionsService = new google.maps.DirectionsService();
        const directionsRenderer = new google.maps.DirectionsRenderer({
          suppressMarkers: true,
          polylineOptions: {
            strokeColor: "#2563eb",
            strokeWeight: 6,
            strokeOpacity: 0.95
          }
        });
        directionsRenderer.setMap(map);

        // --- Advanced Markers using external SVGs in same folder ---
        // Create <img> nodes for pickup & drop icons
        const pickupImg = document.createElement("img");
        pickupImg.src = "restaurant.svg";
        pickupImg.className = "marker-img";
        pickupImg.alt = "Pickup (Restaurant)";

        const dropImg = document.createElement("img");
        dropImg.src = "home.svg";
        dropImg.className = "marker-img";
        dropImg.alt = "Drop (Home)";

        let pickupMarker = null,
          dropMarker = null;

        function ensurePickupMarker(pos) {
          if (!pickupMarker) {
            pickupMarker = new google.maps.marker.AdvancedMarkerElement({
              map,
              position: pos,
              content: pickupImg,
              title: "Pickup (Restaurant)"
            });
          } else {
            pickupMarker.position = pos;
          }
        }

        function ensureDropMarker(pos) {
          if (!dropMarker) {
            dropMarker = new google.maps.marker.AdvancedMarkerElement({
              map,
              position: pos,
              content: dropImg,
              title: "Drop (Home)"
            });
          } else {
            dropMarker.position = pos;
          }
        }

        // Driver — Advanced Marker with rotatable bike.svg
        let driverMarker = null,
          driverImg = null;

        function ensureDriverMarker() {
          if (driverMarker) return;
          driverImg = document.createElement("img");
          driverImg.src = "bike.svg"; // same folder
          driverImg.className = "marker-img marker-rot";
          driverImg.alt = "Driver (Bike)";
          driverMarker = new google.maps.marker.AdvancedMarkerElement({
            map,
            position: {
              lat: 0,
              lng: 0
            },
            content: driverImg,
            title: "Driver (Bike)"
          });
        }
        // -----------------------------------------------------------

        function fitBoundsTo(p, d, drv) {
          const b = new google.maps.LatLngBounds();
          if (p) b.extend(p);
          if (d) b.extend(d);
          if (drv) b.extend(drv);
          if (!b.isEmpty()) map.fitBounds(b, {
            top: 80,
            bottom: 80,
            left: 80,
            right: 380
          });
        }

        function viewForward(a, b) {
          try {
            map.setHeading(bearing(a, b));
            map.setTilt(60);
          } catch {}
        }

        let haveFit = false;
        let lastLegKey = null;

        // Firestore realtime
        const ref = doc(db, 'orders_live', ORDER_ID);
        onSnapshot(ref, (snap) => {
          if (!snap.exists()) {
            $status.textContent = 'Not found';
            return;
          }
          const d = snap.data() || {};

          $status.textContent = d.status ?? '—';
          try {
            $updatedAt.textContent = d.updated_at?.toDate ? d.updated_at.toDate().toLocaleString() : '—';
          } catch {
            $updatedAt.textContent = '—';
          }

          const p = (Number.isFinite(d?.pickup?.lat) && Number.isFinite(d?.pickup?.lng)) ? gLL(+d.pickup.lat, +d.pickup.lng) : null;
          const q = (Number.isFinite(d?.drop?.lat) && Number.isFinite(d?.drop?.lng)) ? gLL(+d.drop.lat, +d.drop.lng) : null;

          const driverId = `${d.driver_id ?? '—'}`;
          $driverId.textContent = driverId;

          const pickupLocationText = locText(d?.pickup?.location);
          const dropLocationText = locText(d?.drop?.location);

          $pickupLoc.textContent = pickupLocationText;
          $dropLoc.textContent = dropLocationText;

          if (p) {
            $pickupLL.textContent = `${fmt(p.lat())}, ${fmt(p.lng())}`;
            $pickupLink.href = `https://maps.google.com/?q=${p.lat()},${p.lng()}`;
            ensurePickupMarker(p);
            if (pickupMarker) pickupMarker.title = pickupLocationText !== '—' ?
              `Pickup: ${pickupLocationText}` :
              `Pickup (Restaurant)`;
          }
          if (q) {
            $dropLL.textContent = `${fmt(q.lat())}, ${fmt(q.lng())}`;
            $dropLink.href = `https://maps.google.com/?q=${q.lat()},${q.lng()}`;
            ensureDropMarker(q);
            if (dropMarker) dropMarker.title = dropLocationText !== '—' ?
              `Drop: ${dropLocationText}` :
              `Drop (Home)`;
          }

          const drvLat = Number(d?.driver_position?.lat ?? d?.lat ?? d?.driver_lat);
          const drvLng = Number(d?.driver_position?.lng ?? d?.lng ?? d?.driver_lng);
          const drv = (Number.isFinite(drvLat) && Number.isFinite(drvLng)) ? gLL(drvLat, drvLng) : null;

          const heading = Number(d?.driver_position?.heading_deg ?? d?.heading_deg);
          const speed = Number(d?.driver_position?.speed_mps ?? d?.speed_mps);

          $driverHeading.textContent = Number.isFinite(heading) ? `${heading.toFixed(0)}°` : '—';
          $driverSpeed.textContent = kmh(speed);

          if (drv) {
            $driverLL.textContent = `${fmt(drvLat)}, ${fmt(drvLng)}`;
            $driverLink.href = `https://maps.google.com/?q=${drvLat},${drvLng}`;
            ensureDriverMarker();
            driverMarker.position = drv;
            if (driverImg) {
              driverImg.style.transform = Number.isFinite(heading) ? `rotate(${heading}deg)` : '';
            }
          }

          // Choose & draw the route leg
          const leg = selectLeg(d.status, p, q, drv);
          if (leg) {
            const key = `${leg.label}:${leg.origin.lat()},${leg.origin.lng()}->${leg.destination.lat()},${leg.destination.lng()}`;
            if (key !== lastLegKey) {
              directionsService.route({
                  origin: leg.origin,
                  destination: leg.destination,
                  travelMode: google.maps.TravelMode.DRIVING
                },
                (result, status) => {
                  if (status === google.maps.DirectionsStatus.OK) {
                    directionsRenderer.setDirections(result);
                    if (!haveFit) {
                      fitBoundsTo(p || null, q || null, drv || null);
                      try {
                        viewForward(leg.origin, leg.destination);
                      } catch {}
                      haveFit = true;
                    }
                  } else {
                    console.warn('Directions error:', status);
                  }
                }
              );
              lastLegKey = key;
            }
          }

          // Follow driver gently + keep forward angle
          if (drv && haveFit) {
            map.panTo(drv);
            if (Number.isFinite(heading)) {
              map.setHeading(heading);
              map.setTilt(60);
            }
          } else if (!haveFit) {
            fitBoundsTo(p || null, q || null, drv || null);
            if (p && q) viewForward(p, q);
            haveFit = true;
          }
        });
      };
    </script>

    <!-- Google Maps JS API (async) with Advanced Markers library -->
    <script
      async
      src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDkTDMXqZFjCYkpa1QPWCsZocpTlPcXvBk&v=weekly&loading=async&libraries=marker&callback=initMap">
    </script>
  <?php endif; ?>
</body>

</html>