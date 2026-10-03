{{-- ID card designs (App\Support\IdCardFormats): the old system's card layout, 252 × 372 px portrait or 372 × 252 px landscape. --}}
<style>
    .identity-card, .horizontal-identity-card { font-family: 'Funnel Display', sans-serif; line-height: 1.2; color: #000; }
    .identity-card .front, .identity-card .back { width: 252px; height: 372px; display: block; }
    .horizontal-identity-card .horizontal-front, .horizontal-identity-card .horizontal-back { width: 372px; height: 252px; display: block; }
    .front-card, .back-card, .horizontal-front-card, .horizontal-back-card { position: relative; overflow: hidden; }
    .front-card .comon-position, .back-card .comon-position { position: absolute; left: 50%; text-align: center; transform: translateX(-50%); width: 100%; margin: 0; }
    .horizontal-front-card .horizontal-comon-position, .horizontal-back-card .horizontal-comon-position { position: absolute; top: 50%; transform: translateY(-50%); width: 95%; }
    .horizontal-back-card .horizontal-signature { display: flex; justify-content: space-between; align-items: center; padding: 0 10px; position: absolute; bottom: 0; width: 95%; }
    .back-card img:not(:first-of-type), .horizontal-signature img, .horizontal-back-card img:not(:first-of-type) { height: 25px; }
    .identity-card h3, .identity-card p, .horizontal-identity-card h3, .horizontal-identity-card p { margin: 0; }
    .identity-card img[src*="/employees/"], .horizontal-identity-card img[src*="/employees/"] { object-fit: cover; }

    /* Gallery: turn the card over on hover. */
    .flip-card { perspective: 1000px; width: 252px; height: 372px; }
    .flip-card.landscape { width: 372px; height: 252px; }
    .flip-card-inner { position: relative; width: 100%; height: 100%; transition: transform 0.8s; transform-style: preserve-3d; }
    .flip-card:hover .flip-card-inner { transform: rotateY(180deg); }
    .flip-card-front, .flip-card-back { position: absolute; inset: 0; backface-visibility: hidden; }
    .flip-card-back { transform: rotateY(180deg); }

    /* Printed at the standard card size (CR80, 54 mm wide). */
    @media print {
        .id-card-print { zoom: 0.81; }
        .id-card-print > * { break-inside: avoid; }
    }
</style>
