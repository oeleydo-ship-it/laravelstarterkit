@extends('layouts.marketing')

@section('title', 'Turn every visitor into a customer')

@section('content')
<main>
    <section class="marketing-hero">
        <div class="container marketing-hero__grid">
            <div class="marketing-hero__copy">
                <span class="eyebrow"><i></i> One workspace. Eight growth tools.</span>
                <h1>Turn website traffic into <em>real growth.</em></h1>
                <p>Capture leads, talk to visitors, collect reviews, book meetings, and follow up automatically—without stitching together a dozen apps.</p>
                <div class="hero-actions">
                    <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="button button--primary">{{ auth()->check() ? 'Open workspace' : 'Start building free' }} <span>→</span></a>
                    <a href="{{ route('features') }}" class="button button--secondary">Explore the platform</a>
                </div>
                <div class="hero-proof"><span>✓ No credit card</span><span>✓ Setup in minutes</span><span>✓ Cancel anytime</span></div>
            </div>
            <div class="product-scene" aria-label="Product dashboard preview">
                <div class="product-window">
                    <div class="window-bar"><span></span><span></span><span></span><small>Growth overview</small></div>
                    <div class="dashboard-shell">
                        <aside><b>◈</b><i></i><i></i><i></i><i></i><i></i></aside>
                        <div class="dashboard-content">
                            <div class="dash-heading"><div><small>THIS MONTH</small><strong>Your growth engine</strong></div><button>+ New campaign</button></div>
                            <div class="metric-grid">
                                <div><small>New leads</small><b>1,284</b><span>↑ 24%</span></div>
                                <div><small>Conversations</small><b>438</b><span>↑ 18%</span></div>
                                <div><small>Bookings</small><b>96</b><span>↑ 31%</span></div>
                            </div>
                            <div class="chart-card"><div class="chart-bars"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div></div>
                            <div class="activity-row"><span class="activity-icon">●</span><p><b>New lead captured</b><small>Pricing page · just now</small></p><strong>Qualified</strong></div>
                        </div>
                    </div>
                </div>
                <div class="floating-card floating-card--chat"><b>● Live chat</b><span>3 conversations waiting</span></div>
                <div class="floating-card floating-card--review"><span>★★★★★</span><b>New 5-star review</b></div>
            </div>
        </div>
    </section>

    <section class="trust-strip"><div class="container"><span>BUILT FOR TEAMS THAT WANT TO GROW</span><div><b>Agencies</b><b>SaaS teams</b><b>Consultants</b><b>Local businesses</b><b>Creators</b></div></div></section>

    <section class="section feature-overview" id="platform">
        <div class="container">
            <div class="section-heading section-heading--split"><div><span class="eyebrow eyebrow--dark">THE COMPLETE TOOLKIT</span><h2>Every customer touchpoint.<br><em>Finally connected.</em></h2></div><p>Replace scattered subscriptions with one focused platform that follows your customer from first click to loyal advocate.</p></div>
            <div class="bento-grid">
                <article class="bento-card bento-card--large"><span class="card-number">01</span><div class="module-icon">↗</div><h3>Live chat that knows your business</h3><p>Turn questions into conversations with a branded widget, team inbox, saved replies, knowledge base, and real-time reporting.</p><div class="mini-chat"><div><i></i><p><b>Sarah</b><span>Can you help me choose a plan?</span></p></div><div class="mini-reply">Absolutely—what size is your team?</div></div></article>
                <article class="bento-card"><span class="card-number">02</span><div class="module-icon module-icon--gold">✦</div><h3>Capture intent</h3><p>Smart popups and campaigns that appear at the right moment—not every moment.</p><span class="text-link">Engage visitors →</span></article>
                <article class="bento-card"><span class="card-number">03</span><div class="module-icon module-icon--coral">⌁</div><h3>Build forms fast</h3><p>Launch lead, contact, survey, and custom forms. Track every submission in one place.</p><span class="text-link">Collect better data →</span></article>
                <article class="bento-card bento-card--wide"><span class="card-number">04</span><div><div class="module-icon module-icon--blue">✉</div><h3>Email that continues the conversation</h3><p>Segment subscribers, build campaigns from templates, and understand opens, clicks, and conversions.</p><span class="text-link">Nurture your audience →</span></div><div class="email-visual"><span>Campaign performance</span><b>68.4%</b><small>OPEN RATE</small><div><i style="width:68%"></i></div></div></article>
            </div>
            <div class="center-action"><a href="{{ route('features') }}" class="button button--ink">See all eight modules <span>→</span></a></div>
        </div>
    </section>

    <section class="workflow-section"><div class="container workflow-grid"><div><span class="eyebrow">ONE CUSTOMER JOURNEY</span><h2>From anonymous click<br>to loyal customer.</h2><p>Your tools share one workspace, so your team sees the full story—not fragments spread across tabs.</p><a href="{{ route('register') }}" class="button button--light">Build your growth stack →</a></div><ol><li><span>01</span><div><b>Attract</b><p>Publish helpful content automatically with Autoblog.</p></div></li><li><span>02</span><div><b>Convert</b><p>Capture attention with Engage campaigns and Forms.</p></div></li><li><span>03</span><div><b>Connect</b><p>Answer questions in Live Chat and nurture with Email.</p></div></li><li><span>04</span><div><b>Grow</b><p>Book appointments, collect Reviews, and show Social Proof.</p></div></li></ol></div></section>

    <section class="section final-cta"><div class="container"><div class="final-cta__card"><span class="eyebrow">START TODAY</span><h2>Less software.<br><em>More momentum.</em></h2><p>Give your team one place to turn attention into lasting customer relationships.</p><a href="{{ route('register') }}" class="button button--primary">Create your workspace <span>→</span></a></div></div></section>
</main>
@endsection
