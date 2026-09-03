@php
    $localization = app(\App\Services\LocalizationService::class);
    $siteName = $site->get('site_name', 'Arcadia');
    $supportEmail = $site->get('support_email', 'support@example.com');
    $registrationNumber = $site->get('registration_number');
    $riskNotice = $site->get('risk_notice', 'Investments involve risk. Returns are not guaranteed.');
    $riskPage = $legalPages->get('risk-disclosure');
    $termsPage = $legalPages->get('terms-and-conditions');
    $privacyPage = $legalPages->get('privacy-policy');
    $certificateUrl = $site->get('certificate_url') ?: ($riskPage ? route('cms.pages.show', $riskPage) : '#legal');
    $termsUrl = $termsPage ? route('cms.pages.show', $termsPage) : '#legal';
    $privacyUrl = $privacyPage ? route('cms.pages.show', $privacyPage) : '#legal';
    $logoPath = $site->get('logo_path');
    $logoUrl = $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) : null;
    $defaultCurrencyModel = $localization->currency($defaultCurrency);
    $defaultCurrencySymbol = $defaultCurrencyModel?->symbol ?? $defaultCurrency;
    $firstPlan = $plans->first();
    $firstPlanCurrency = $firstPlan ? $localization->currency($firstPlan->currency) : $defaultCurrencyModel;
    $firstPlanDivisor = 10 ** ($firstPlanCurrency?->decimal_places ?? 2);
    $calculatorMinimum = $firstPlan ? max(1, $firstPlan->minimum_amount_minor / $firstPlanDivisor) : 100;
    $calculatorMaximum = $firstPlan?->maximum_amount_minor
        ? $firstPlan->maximum_amount_minor / $firstPlanDivisor
        : max($calculatorMinimum * 20, 100000);
    $referralEnabled = $referralSetting?->is_enabled === true;
    $referralRate = ($referralSetting?->investment_commission_bps ?? 0) / 100;
    $referralHoldDays = $referralSetting?->hold_days ?? 0;
    $maskName = static function (?string $name): string {
        $name = trim($name ?: 'Member');

        return mb_substr($name, 0, 1).'•••'.mb_substr($name, -1);
    };
    $money = static fn (int $minor, string $currency): string => app(\App\Services\LocalizationService::class)->formatMinor($minor, $currency);
    $fallbackPayments = [
        ['name' => 'Bitcoin', 'code' => 'BTC'],
        ['name' => 'Ethereum', 'code' => 'ETH'],
        ['name' => 'Tether', 'code' => 'USDT'],
        ['name' => 'Litecoin', 'code' => 'LTC'],
        ['name' => 'PayPal', 'code' => 'PP'],
        ['name' => 'Bank transfer', 'code' => 'BANK'],
    ];
    $socialLinks = [
        'Telegram' => $site->get('telegram_url'),
        'Facebook' => $site->get('facebook_url'),
        'Instagram' => $site->get('instagram_url'),
        'X / Twitter' => $site->get('twitter_url'),
        'LinkedIn' => $site->get('linkedin_url'),
        'YouTube' => $site->get('youtube_url'),
    ];
    $faqs = [
        ["How do I start with {$siteName}?", 'Create an account, complete the required verification, choose a published plan, and fund it through an enabled payment method.'],
        ['How are projected returns calculated?', 'Every plan publishes its rate model, term, calculation frequency, limits, and lock period before you commit funds.'],
        ['Can I withdraw at any time?', 'Withdrawal availability follows the selected plan terms, account verification status, and the configured manual payout review process.'],
        ['Are projected returns guaranteed?', 'No. Projections are illustrations based on plan terms. Review the risk disclosure and seek independent advice before investing.'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#070907">
        <meta name="description" content="{{ $siteName }} is a transparent investment platform with visible plan terms, activity, and account controls.">
        @if ($site->get('favicon_path'))
            <link rel="icon" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($site->get('favicon_path')) }}">
        @endif
        <title>{{ $siteName }} — Capital in motion</title>
        <script>document.documentElement.classList.add('has-js');</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="public-investment-home">
        <div class="public-topline">
            <div class="public-shell public-topline__inner">
                <span><i></i> Platform systems operational</span>
                <span class="hidden sm:inline">Live ledger · Manual payout review · Account controls</span>
                <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
            </div>
        </div>

        <header class="public-header" data-public-header>
            <div class="public-shell public-header__inner">
                <a href="{{ route('home') }}" class="public-brand" aria-label="{{ $siteName }} home">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}">
                    @else
                        <span class="public-brand__mark">A</span>
                        <span>{{ strtoupper($siteName) }}</span>
                    @endif
                </a>

                <nav class="public-nav" aria-label="Primary navigation">
                    <a href="#plans">Plans</a>
                    <a href="#calculator">Calculator</a>
                    <a href="#process">How it works</a>
                    <a href="#activity">Transactions</a>
                    <a href="#security">Security</a>
                </nav>

                <div class="public-header__actions">
                    <a href="{{ route('login') }}" class="public-login">Log in</a>
                    <a href="{{ route('register') }}" class="public-button public-button--signal">Open account <span>→</span></a>
                    <button type="button" class="public-menu-toggle" data-public-menu-toggle aria-label="Toggle navigation" aria-expanded="false">
                        <span></span><span></span>
                    </button>
                </div>
            </div>
            <nav class="public-mobile-nav" data-public-mobile-nav aria-label="Mobile navigation">
                <a href="#plans">Investment plans</a>
                <a href="#calculator">Profit calculator</a>
                <a href="#process">How it works</a>
                <a href="#activity">Live transactions</a>
                <a href="#security">Security</a>
                <a href="{{ route('login') }}">Log in</a>
            </nav>
        </header>

        <main>
            <section class="public-hero">
                <div class="public-hero__grid" aria-hidden="true"></div>
                <div class="public-hero__flare" aria-hidden="true"></div>
                <div class="public-shell public-hero__inner">
                    <div class="public-hero__copy">
                        <div class="public-kicker"><span>01</span> PRIVATE CAPITAL INFRASTRUCTURE</div>
                        <h1>Capital<br>never<br><em>sleeps.</em></h1>
                        <p class="public-hero__lead">A precision-built investment platform where every plan, return, deposit, and payout stays visible from first move to final settlement.</p>
                        <div class="public-hero__actions">
                            <a href="{{ route('register') }}" class="public-button public-button--signal public-button--large">Start investing <span>→</span></a>
                            <a href="#plans" class="public-button public-button--ghost public-button--large">Explore plans <span>↓</span></a>
                        </div>
                        <div class="public-hero__proof">
                            <span><b>01</b> Transparent plan terms</span>
                            <span><b>02</b> Tracked money movement</span>
                            <span><b>03</b> Human support</span>
                        </div>
                    </div>

                    <div class="public-terminal-wrap">
                        <div class="public-terminal">
                            <div class="public-terminal__bar">
                                <span>ARC / CAPITAL INDEX</span>
                                <span class="public-live"><i></i> LIVE</span>
                            </div>
                            <div class="public-terminal__body">
                                <div class="public-terminal__headline">
                                    <div>
                                        <span>Total capital processed</span>
                                        <strong>{{ $money($stats['deposited_minor'], $defaultCurrency) }}</strong>
                                    </div>
                                    <small>{{ $defaultCurrency }}<br>BASE</small>
                                </div>
                                <div class="public-market-chart">
                                    <div class="public-market-chart__labels"><span>ACTIVITY INDEX</span><span>12M VIEW</span></div>
                                    <svg viewBox="0 0 640 240" preserveAspectRatio="none" role="img" aria-label="Platform activity chart">
                                        <defs>
                                            <linearGradient id="public-chart-fill" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0" stop-color="#c8ff3d" stop-opacity=".28"/>
                                                <stop offset="1" stop-color="#c8ff3d" stop-opacity="0"/>
                                            </linearGradient>
                                        </defs>
                                        <path class="public-chart-grid" d="M0 30H640M0 90H640M0 150H640M0 210H640"/>
                                        <path class="public-chart-area" d="M0 202C34 205 48 181 79 187C109 193 129 158 166 169C202 180 220 137 257 145C294 153 311 117 352 124C391 131 408 91 448 101C489 111 514 62 551 78C587 93 610 37 640 43V240H0Z"/>
                                        <path class="public-chart-line" d="M0 202C34 205 48 181 79 187C109 193 129 158 166 169C202 180 220 137 257 145C294 153 311 117 352 124C391 131 408 91 448 101C489 111 514 62 551 78C587 93 610 37 640 43"/>
                                    </svg>
                                    <div class="public-market-chart__axis"><span>JAN</span><span>MAR</span><span>JUN</span><span>SEP</span><span>DEC</span></div>
                                </div>
                                <div class="public-terminal__metrics">
                                    <div><span>ACTIVE MEMBERS</span><strong>{{ number_format($stats['members']) }}</strong></div>
                                    <div><span>TOTAL PAID OUT</span><strong>{{ $money($stats['withdrawn_minor'], $defaultCurrency) }}</strong></div>
                                    <div><span>ONLINE</span><strong>{{ number_format($stats['online_days']) }} DAYS</strong></div>
                                </div>
                            </div>
                            <div class="public-terminal__footer">
                                <span>ENCRYPTED SESSION</span><span>LEDGER CONNECTED</span><span>STATUS: NORMAL</span>
                            </div>
                        </div>
                        <div class="public-terminal-tag"><span>SETTLEMENT NETWORK</span><strong>{{ $money($stats['withdrawn_minor'], $defaultCurrency) }}</strong><small>RECORDED PAYOUTS</small></div>
                    </div>
                </div>

                <div class="public-hero__stats">
                    <div class="public-shell public-hero__stats-grid">
                        <div><span>01 / TOTAL DEPOSITED</span><strong>{{ $money($stats['deposited_minor'], $defaultCurrency) }}</strong></div>
                        <div><span>02 / TOTAL WITHDRAWN</span><strong>{{ $money($stats['withdrawn_minor'], $defaultCurrency) }}</strong></div>
                        <div><span>03 / ACTIVE MEMBERS</span><strong>{{ number_format($stats['members']) }}+</strong></div>
                        <div><span>04 / PLATFORM AGE</span><strong>{{ number_format($stats['online_days']) }} DAYS</strong></div>
                    </div>
                </div>
            </section>

            <div class="public-market-tape" aria-label="Platform highlights">
                <div class="public-market-tape__track">
                    <span>VISIBLE TERMS</span><i>✦</i><span>DAILY ACCOUNTING</span><i>✦</i><span>MULTI-CURRENCY</span><i>✦</i><span>MANUAL PAYOUT CONTROL</span><i>✦</i><span>AUDITABLE LEDGER</span><i>✦</i><span>VISIBLE TERMS</span><i>✦</i><span>DAILY ACCOUNTING</span><i>✦</i><span>MULTI-CURRENCY</span><i>✦</i><span>MANUAL PAYOUT CONTROL</span><i>✦</i><span>AUDITABLE LEDGER</span><i>✦</i>
                </div>
            </div>

            <section class="public-payment-rail">
                <div class="public-shell public-payment-rail__inner">
                    <div class="public-payment-rail__copy"><span>FUNDING NETWORK</span><p>Enabled rails for moving capital.</p></div>
                    <div class="public-payment-rail__methods">
                        @forelse ($gateways as $gateway)
                            <div><b>{{ strtoupper(mb_substr($gateway->name ?: $gateway->driver, 0, 2)) }}</b><span>{{ $gateway->name }}</span></div>
                        @empty
                            @foreach ($fallbackPayments as $payment)
                                <div><b>{{ $payment['code'] }}</b><span>{{ $payment['name'] }}</span></div>
                            @endforeach
                        @endforelse
                    </div>
                </div>
            </section>

            <section id="plans" class="public-section public-section--ivory">
                <div class="public-shell">
                    <div class="public-section-heading public-reveal">
                        <div class="public-kicker public-kicker--dark"><span>02</span> INVESTMENT STRATEGIES</div>
                        <h2>Choose the pace.<br><em>Keep the control.</em></h2>
                        <p>Compare the real numbers—rate, term, limits, calculation schedule, and access—before your capital moves.</p>
                    </div>

                    <div class="public-plan-list">
                        <div class="public-plan-list__header">
                            <span>PLAN</span><span>PROJECTED RATE</span><span>TERM</span><span>CAPITAL RANGE</span><span>ACCESS</span><span></span>
                        </div>
                        @forelse ($plans as $index => $plan)
                            <article class="public-plan-row public-reveal" data-delay="{{ min($index * 60, 240) }}">
                                <div class="public-plan-row__identity">
                                    <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    <div><h3>{{ $plan->name }}</h3><p>{{ $plan->description ?: str($plan->risk_rating)->title().' risk strategy' }}</p></div>
                                </div>
                                <div class="public-plan-row__rate"><strong>{{ number_format($plan->projected_rate_bps / 100, 2) }}%</strong><span>{{ $plan->rate_type === 'term_simple' ? 'PER TERM' : 'PER ANNUM' }}</span></div>
                                <div class="public-plan-row__metric"><strong>{{ $plan->term_days }}</strong><span>DAYS · {{ strtoupper($plan->calculation_frequency) }}</span></div>
                                <div class="public-plan-row__metric"><strong>{{ $money($plan->minimum_amount_minor, $plan->currency) }}</strong><span>TO {{ $plan->maximum_amount_minor ? $money($plan->maximum_amount_minor, $plan->currency) : 'NO FIXED CAP' }}</span></div>
                                <div class="public-plan-row__metric"><strong>{{ $plan->lock_days === 0 ? 'OPEN' : $plan->lock_days.' DAYS' }}</strong><span>{{ $plan->lock_days === 0 ? 'NO LOCK PERIOD' : 'LOCK PERIOD' }}</span></div>
                                <a href="{{ route('register') }}" aria-label="Open {{ $plan->name }} plan">→</a>
                            </article>
                        @empty
                            <div class="public-empty-state"><span>NO / LIVE / STRATEGIES</span><h3>The next plan release is being prepared.</h3><p>Published plans will appear here with complete rates, terms, limits, and access conditions.</p><a href="{{ route('register') }}" class="public-button public-button--ink">Create account <span>→</span></a></div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section id="calculator" class="public-calculator-section">
                <div class="public-shell public-calculator-grid">
                    <div class="public-calculator-copy public-reveal">
                        <div class="public-kicker"><span>03</span> RETURN SIMULATOR</div>
                        <h2>Run the<br><em>numbers.</em></h2>
                        <p>Model a published strategy before you commit. The calculation changes with the selected plan’s actual rate model and term.</p>
                        <div class="public-calculator-note"><span>!</span><p>Illustrative projection only. It is not a guarantee of future performance.</p></div>
                    </div>

                    <div class="public-calculator public-reveal" data-delay="120">
                        <div class="public-calculator__top"><span>PROJECTION TERMINAL</span><span>MODEL / 01</span></div>
                        <div class="public-calculator__fields">
                            <label>
                                <span>SELECT STRATEGY</span>
                                <select id="public-calculator-plan" @disabled($plans->isEmpty())>
                                    @forelse ($plans as $plan)
                                        @php
                                            $planCurrency = $localization->currency($plan->currency);
                                            $planDivisor = 10 ** ($planCurrency?->decimal_places ?? 2);
                                            $planMinimum = max(1, $plan->minimum_amount_minor / $planDivisor);
                                            $planMaximum = $plan->maximum_amount_minor
                                                ? $plan->maximum_amount_minor / $planDivisor
                                                : max($planMinimum * 20, 100000);
                                        @endphp
                                        <option
                                            value="{{ $plan->id }}"
                                            data-rate="{{ $plan->projected_rate_bps / 100 }}"
                                            data-rate-type="{{ $plan->rate_type }}"
                                            data-term="{{ $plan->term_days }}"
                                            data-frequency="{{ $plan->calculation_frequency }}"
                                            data-currency="{{ $plan->currency }}"
                                            data-min="{{ $planMinimum }}"
                                            data-max="{{ $planMaximum }}"
                                        >{{ $plan->name }} / {{ number_format($plan->projected_rate_bps / 100, 2) }}%</option>
                                    @empty
                                        <option data-rate="0" data-rate-type="annual_simple" data-term="365" data-frequency="maturity" data-currency="{{ $defaultCurrency }}" data-min="100" data-max="100000">No published strategy</option>
                                    @endforelse
                                </select>
                            </label>
                            <label>
                                <span>CAPITAL AMOUNT</span>
                                <div class="public-amount-input"><b data-calculator-symbol>{{ $firstPlanCurrency?->symbol ?? $defaultCurrencySymbol }}</b><input id="public-calculator-amount" type="number" min="{{ $calculatorMinimum }}" max="{{ $calculatorMaximum }}" step="1" value="{{ $calculatorMinimum }}"></div>
                            </label>
                        </div>
                        <input id="public-calculator-range" class="public-range" type="range" min="{{ $calculatorMinimum }}" max="{{ $calculatorMaximum }}" step="1" value="{{ $calculatorMinimum }}">
                        <div class="public-range-labels"><span data-calculator-min>{{ number_format($calculatorMinimum, 0) }}</span><span data-calculator-max>{{ number_format($calculatorMaximum, 0) }}</span></div>
                        <div class="public-calculator__results">
                            <div><span>EST. DAILY</span><strong id="public-calculator-daily">—</strong></div>
                            <div><span>PROJECTED PROFIT</span><strong id="public-calculator-profit">—</strong></div>
                            <div class="public-calculator__total"><span>EST. AT MATURITY</span><strong id="public-calculator-total">—</strong><small id="public-calculator-caption">SELECT A STRATEGY</small></div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="approach" class="public-section public-section--white">
                <div class="public-shell public-manifesto">
                    <div class="public-manifesto__statement public-reveal">
                        <div class="public-kicker public-kicker--dark"><span>04</span> THE OPERATING STANDARD</div>
                        <h2>No smoke.<br>No mystery.<br><em>Just the numbers.</em></h2>
                    </div>
                    <div class="public-manifesto__list">
                        @foreach ([
                            ['01', 'Terms before capital', 'Every published strategy shows its projected rate, model, duration, limits, and lock period before funding.'],
                            ['02', 'Money with a trail', 'Deposits, returns, commissions, and payouts move through recorded wallet and ledger entries.'],
                            ['03', 'Control at every edge', 'Verification, staff permissions, manual payout review, and audit logs create accountable operations.'],
                            ['04', 'Support with context', 'A complete ticket conversation keeps attachments, status, ownership, and replies in one protected workspace.'],
                        ] as $principle)
                            <article class="public-principle public-reveal">
                                <span>{{ $principle[0] }}</span><div><h3>{{ $principle[1] }}</h3><p>{{ $principle[2] }}</p></div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="process" class="public-process">
                <div class="public-shell">
                    <div class="public-process__heading public-reveal">
                        <div class="public-kicker"><span>05</span> FROM ZERO TO INVESTED</div>
                        <h2>Four moves.<br><em>One clear path.</em></h2>
                    </div>
                    <div class="public-process__steps">
                        @foreach ([
                            ['CREATE', 'Open your account', 'Register and secure your access.'],
                            ['VERIFY', 'Complete verification', 'Submit the required identity information.'],
                            ['ALLOCATE', 'Choose and fund', 'Accept a published plan and deposit.'],
                            ['TRACK', 'Earn and withdraw', 'Follow returns and request eligible payouts.'],
                        ] as $index => $step)
                            <article class="public-process-step public-reveal" data-delay="{{ $index * 70 }}">
                                <div><span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><i></i></div>
                                <small>{{ $step[0] }}</small><h3>{{ $step[1] }}</h3><p>{{ $step[2] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="activity" class="public-section public-section--ivory">
                <div class="public-shell">
                    <div class="public-activity-heading public-reveal">
                        <div><div class="public-kicker public-kicker--dark"><span>06</span> PLATFORM TAPE</div><h2>Money in motion.</h2></div>
                        <div class="public-activity-tabs" role="tablist" aria-label="Transaction type">
                            <button type="button" class="is-active" data-activity-tab="deposits">LATEST DEPOSITS</button>
                            <button type="button" data-activity-tab="withdrawals">LATEST PAYOUTS</button>
                        </div>
                    </div>

                    <div class="public-activity-table public-reveal">
                        <div data-activity-panel="deposits">
                            <div class="public-activity-row public-activity-row--header"><span>MEMBER</span><span>NETWORK</span><span>AMOUNT</span><span>RECORDED</span></div>
                            @forelse ($deposits as $deposit)
                                <div class="public-activity-row"><span><i>{{ strtoupper(mb_substr($deposit->user?->name ?: 'M', 0, 1)) }}</i>{{ $maskName($deposit->user?->name) }}</span><span>{{ $deposit->paymentIntent?->paymentGateway?->name ?? str($deposit->method)->replace('_', ' ')->title() }}</span><strong>+ {{ $money($deposit->amount_minor, $deposit->currency) }}</strong><time>{{ $deposit->created_at?->diffForHumans() }}</time></div>
                            @empty
                                <div class="public-activity-empty">Completed deposits will enter the public activity tape here.</div>
                            @endforelse
                        </div>
                        <div class="hidden" data-activity-panel="withdrawals">
                            <div class="public-activity-row public-activity-row--header"><span>MEMBER</span><span>NETWORK</span><span>AMOUNT</span><span>RECORDED</span></div>
                            @forelse ($withdrawals as $withdrawal)
                                <div class="public-activity-row"><span><i>{{ strtoupper(mb_substr($withdrawal->user?->name ?: 'M', 0, 1)) }}</i>{{ $maskName($withdrawal->user?->name) }}</span><span>{{ $withdrawal->withdrawalMethod?->name ?? 'Manual payout' }}</span><strong>− {{ $money($withdrawal->gross_amount_minor, $withdrawal->currency) }}</strong><time>{{ $withdrawal->created_at?->diffForHumans() }}</time></div>
                            @empty
                                <div class="public-activity-empty">Completed payouts will enter the public activity tape here.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="public-referral">
                <div class="public-shell public-referral__grid">
                    <div class="public-referral__copy public-reveal">
                        <div class="public-kicker public-kicker--dark"><span>07</span> PARTNER NETWORK</div>
                        <h2>Your network<br>can <em>compound.</em></h2>
                        <p>Share your unique link. Eligible direct investment activity is tracked automatically and reviewed under the configured referral policy.</p>
                        <a href="{{ route('register') }}" class="public-button public-button--ink public-button--large">Get your referral link <span>→</span></a>
                    </div>
                    <div class="public-referral__terms public-reveal" data-delay="120">
                        <div class="public-referral__rate"><span>DIRECT COMMISSION</span><strong>{{ $referralEnabled ? number_format($referralRate, 2).'%' : 'PAUSED' }}</strong><small>{{ $referralEnabled ? 'ON ELIGIBLE INVESTMENTS' : 'CONTROLLED BY OPERATIONS' }}</small></div>
                        <div class="public-referral__details">
                            <div><span>ATTRIBUTION</span><strong>UNIQUE LINK</strong></div>
                            <div><span>HOLD PERIOD</span><strong>{{ $referralEnabled ? $referralHoldDays.' DAYS' : '—' }}</strong></div>
                            <div><span>VISIBILITY</span><strong>LIVE DASHBOARD</strong></div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="security" class="public-security">
                <div class="public-security__graphic" aria-hidden="true"><div class="public-security__orbit"><span></span><i></i></div><strong>SECURE<br>BY DESIGN</strong></div>
                <div class="public-shell public-security__inner">
                    <div class="public-security__copy public-reveal">
                        <div class="public-kicker"><span>08</span> TRUST ARCHITECTURE</div>
                        <h2>Protection is<br>not a <em>badge.</em></h2>
                        <p>It is the way access, data, approvals, and financial records are designed to behave.</p>
                    </div>
                    <div class="public-security__controls public-reveal" data-delay="120">
                        <div><span>01</span><p><strong>Encrypted sensitive configuration</strong>Secrets and protected settings are encrypted before storage.</p></div>
                        <div><span>02</span><p><strong>Role-based staff access</strong>Operations, finance, compliance, and support permissions stay separated.</p></div>
                        <div><span>03</span><p><strong>Manual payout authorization</strong>Outgoing funds move through a visible review and settlement workflow.</p></div>
                        <div><span>04</span><p><strong>Auditable financial records</strong>Wallet movements retain references, status history, and ledger context.</p></div>
                    </div>
                </div>
                <div id="legal" class="public-shell public-legal-strip">
                    <div><span>COMPANY RECORD</span><strong>{{ $registrationNumber ?: 'Not published' }}</strong></div>
                    <div><span>RISK DISCLOSURE</span><strong>AVAILABLE ONLINE</strong></div>
                    <a href="{{ $certificateUrl }}" target="{{ str_starts_with($certificateUrl, 'http') ? '_blank' : '_self' }}" rel="noreferrer">VIEW DOCUMENTS <span>→</span></a>
                </div>
            </section>

            <section class="public-section public-section--white">
                <div class="public-shell">
                    <div class="public-section-heading public-section-heading--compact public-reveal">
                        <div class="public-kicker public-kicker--dark"><span>09</span> MEMBER PERSPECTIVE</div>
                        <h2>Proof sounds<br><em>like people.</em></h2>
                    </div>
                    <div class="public-testimonials">
                        @forelse ($testimonials as $index => $testimonial)
                            <article class="public-testimonial public-reveal" data-delay="{{ $index * 70 }}">
                                <span>“</span>
                                <blockquote>{{ $testimonial->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($testimonial->body), 210) }}</blockquote>
                                <div><strong>{{ $testimonial->title }}</strong><a href="{{ route('cms.pages.show', $testimonial) }}">READ STORY →</a></div>
                            </article>
                        @empty
                            @foreach ([
                                ['CLARITY', 'Plan terms are visible before any commitment is made.'],
                                ['CONTROL', 'Every important action has a status, owner, and recorded next step.'],
                                ['CONTEXT', 'Dashboards connect balances, investments, activity, and support.'],
                            ] as $index => $value)
                                <article class="public-testimonial public-testimonial--principle public-reveal" data-delay="{{ $index * 70 }}"><span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><blockquote>{{ $value[1] }}</blockquote><div><strong>{{ $value[0] }}</strong><small>THE {{ strtoupper($siteName) }} STANDARD</small></div></article>
                            @endforeach
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="public-news">
                <div class="public-shell">
                    <div class="public-news__heading public-reveal"><div class="public-kicker"><span>10</span> INTELLIGENCE DESK</div><h2>Latest from<br>the platform.</h2></div>
                    <div class="public-news__list">
                        @forelse ($news as $index => $article)
                            <article class="public-news-item public-reveal" data-delay="{{ $index * 60 }}">
                                <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <div><small>{{ $article->published_at?->format('M d, Y') ?: $article->updated_at?->format('M d, Y') }} / ANNOUNCEMENT</small><h3>{{ $article->title }}</h3><p>{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->body), 150) }}</p></div>
                                <a href="{{ route('cms.pages.show', $article) }}" aria-label="Read {{ $article->title }}">→</a>
                            </article>
                        @empty
                            <div class="public-news-empty"><span>NEWSROOM / READY</span><h3>Updates will publish here.</h3><p>Create and publish a CMS page to place the latest platform announcement on the public desk.</p></div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="public-faq">
                <div class="public-shell public-faq__grid">
                    <div class="public-faq__heading public-reveal"><div class="public-kicker public-kicker--dark"><span>11</span> DUE DILIGENCE</div><h2>Ask before<br>you move.</h2><p>Good decisions begin with direct answers.</p></div>
                    <div class="public-faq__list">
                        @foreach ($faqs as $index => $faq)
                            <details class="public-faq-item public-reveal">
                                <summary><span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><strong>{{ $faq[0] }}</strong><i>+</i></summary>
                                <p>{{ $faq[1] }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="public-final-cta">
                <div class="public-final-cta__lines" aria-hidden="true"></div>
                <div class="public-shell public-final-cta__inner public-reveal">
                    <span>YOUR CAPITAL / YOUR DECISION</span>
                    <h2>Make the next<br>move count.</h2>
                    <a href="{{ route('register') }}" class="public-final-cta__button"><span>OPEN YOUR ACCOUNT</span><i>→</i></a>
                </div>
            </section>
        </main>

        <footer class="public-footer">
            <div class="public-shell">
                <div class="public-footer__top">
                    <div class="public-footer__brand">
                        <a href="{{ route('home') }}" class="public-brand">
                            @if ($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $siteName }}">@else<span class="public-brand__mark">A</span><span>{{ strtoupper($siteName) }}</span>@endif
                        </a>
                        <p>Capital infrastructure built around visible terms, accountable workflows, and clear records.</p>
                    </div>
                    <div><span>PLATFORM</span><a href="#plans">Investment plans</a><a href="#calculator">Return calculator</a><a href="#activity">Live transactions</a><a href="#process">How it works</a></div>
                    <div><span>LEGAL</span><a href="{{ $certificateUrl }}">Risk disclosure</a><a href="{{ $termsUrl }}">Terms & conditions</a><a href="{{ $privacyUrl }}">Privacy policy</a><a href="mailto:{{ $supportEmail }}">Contact support</a></div>
                    <div><span>NETWORK</span>@forelse (collect($socialLinks)->filter() as $label => $url)<a href="{{ $url }}" target="_blank" rel="noreferrer">{{ $label }} →</a>@empty<p>Public channels will appear here when configured.</p>@endforelse</div>
                </div>
                <div class="public-footer__bottom"><span>© {{ now()->year }} {{ strtoupper($siteName) }}</span><p>{{ $riskNotice }}</p><span>{{ $registrationNumber ? 'REG / '.$registrationNumber : 'PRIVATE PLATFORM' }}</span></div>
            </div>
        </footer>

        @if ($site->get('cookie_consent_enabled') === '1')
            <div class="public-cookie" id="public-cookie-banner">
                <span>PRIVACY / NOTICE</span>
                <div><strong>{{ $site->get('cookie_consent_title', 'Your privacy matters') }}</strong><p>{{ $site->get('cookie_consent_message', 'We use cookies to improve your experience.') }}</p></div>
                @if ($site->get('cookie_policy_url'))<a href="{{ $site->get('cookie_policy_url') }}">POLICY →</a>@endif
                <button type="button" data-cookie-accept>{{ $site->get('cookie_accept_label', 'ACCEPT') }}</button>
            </div>
        @endif

        <script>
            (() => {
                const menuButton = document.querySelector('[data-public-menu-toggle]');
                const mobileMenu = document.querySelector('[data-public-mobile-nav]');
                menuButton?.addEventListener('click', () => {
                    const isOpen = mobileMenu?.classList.toggle('is-open') ?? false;
                    menuButton.classList.toggle('is-open', isOpen);
                    menuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
                mobileMenu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
                    mobileMenu.classList.remove('is-open');
                    menuButton?.classList.remove('is-open');
                    menuButton?.setAttribute('aria-expanded', 'false');
                }));

                const calculatorPlan = document.querySelector('#public-calculator-plan');
                const calculatorAmount = document.querySelector('#public-calculator-amount');
                const calculatorRange = document.querySelector('#public-calculator-range');
                const calculatorDaily = document.querySelector('#public-calculator-daily');
                const calculatorProfit = document.querySelector('#public-calculator-profit');
                const calculatorTotal = document.querySelector('#public-calculator-total');
                const calculatorCaption = document.querySelector('#public-calculator-caption');
                const calculatorSymbol = document.querySelector('[data-calculator-symbol]');
                const calculatorMin = document.querySelector('[data-calculator-min]');
                const calculatorMax = document.querySelector('[data-calculator-max]');

                const formatCurrency = (value, currency) => {
                    try {
                        return new Intl.NumberFormat(undefined, { style: 'currency', currency, maximumFractionDigits: 2 }).format(value);
                    } catch (error) {
                        return `${currency} ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 2 })}`;
                    }
                };

                const updateCalculator = (resetAmount = false) => {
                    const option = calculatorPlan?.selectedOptions[0];
                    if (!option || !calculatorAmount || !calculatorRange) return;

                    const minimum = Number(option.dataset.min || 1);
                    const maximum = Number(option.dataset.max || Math.max(minimum * 20, 100000));
                    const currency = option.dataset.currency || @json($defaultCurrency);
                    const rate = Number(option.dataset.rate || 0) / 100;
                    const term = Number(option.dataset.term || 365);
                    const rateType = option.dataset.rateType || 'annual_simple';
                    let principal = Number(calculatorAmount.value || minimum);

                    if (resetAmount) principal = minimum;
                    principal = Math.min(Math.max(principal, minimum), maximum);
                    calculatorAmount.min = minimum;
                    calculatorAmount.max = maximum;
                    calculatorAmount.value = principal;
                    calculatorRange.min = minimum;
                    calculatorRange.max = maximum;
                    calculatorRange.value = principal;
                    if (calculatorMin) calculatorMin.textContent = minimum.toLocaleString();
                    if (calculatorMax) calculatorMax.textContent = maximum.toLocaleString();
                    if (calculatorSymbol) calculatorSymbol.textContent = currency;

                    const projectedProfit = rateType === 'term_simple'
                        ? principal * rate
                        : principal * rate * term / 365;
                    const dailyProjection = term > 0 ? projectedProfit / term : 0;
                    const maturityValue = principal + projectedProfit;

                    if (calculatorDaily) calculatorDaily.textContent = formatCurrency(dailyProjection, currency);
                    if (calculatorProfit) calculatorProfit.textContent = formatCurrency(projectedProfit, currency);
                    if (calculatorTotal) calculatorTotal.textContent = formatCurrency(maturityValue, currency);
                    if (calculatorCaption) calculatorCaption.textContent = `${term} DAYS / ${(option.dataset.frequency || 'maturity').toUpperCase()}`;
                };

                calculatorPlan?.addEventListener('change', () => updateCalculator(true));
                calculatorAmount?.addEventListener('input', () => {
                    if (calculatorRange) calculatorRange.value = calculatorAmount.value;
                    updateCalculator();
                });
                calculatorRange?.addEventListener('input', () => {
                    if (calculatorAmount) calculatorAmount.value = calculatorRange.value;
                    updateCalculator();
                });
                updateCalculator();

                const activityButtons = document.querySelectorAll('[data-activity-tab]');
                const activityPanels = document.querySelectorAll('[data-activity-panel]');
                activityButtons.forEach((button) => button.addEventListener('click', () => {
                    activityButtons.forEach((item) => item.classList.toggle('is-active', item === button));
                    activityPanels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.activityPanel !== button.dataset.activityTab));
                }));

                const revealItems = document.querySelectorAll('.public-reveal');
                if ('IntersectionObserver' in window) {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (!entry.isIntersecting) return;
                            entry.target.style.transitionDelay = `${entry.target.dataset.delay || 0}ms`;
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        });
                    }, { threshold: 0.12 });
                    revealItems.forEach((item) => observer.observe(item));
                } else {
                    revealItems.forEach((item) => item.classList.add('is-visible'));
                }

                const cookieBanner = document.querySelector('#public-cookie-banner');
                if (localStorage.getItem('cookie-consent') === 'accepted') cookieBanner?.remove();
                document.querySelector('[data-cookie-accept]')?.addEventListener('click', () => {
                    localStorage.setItem('cookie-consent', 'accepted');
                    cookieBanner?.remove();
                });
            })();
        </script>
    </body>
</html>
