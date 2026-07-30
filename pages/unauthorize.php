<?php
unset($_SESSION['hata']);
http_response_code(403);
?>

<style>
    .unauthorized-page {
        width: 100%;
        min-height: 100%;
        flex: 1;
        display: grid;
        place-items: center;
        padding: 2rem 1rem;
        box-sizing: border-box;
    }

    .unauthorized-card {
        width: 100%;
        max-width: 32rem;
        overflow: hidden;
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        background: var(--card);
        color: var(--foreground);
        box-shadow: 0 1px 2px rgb(0 0 0 / 0.04), 0 8px 24px rgb(0 0 0 / 0.04);
    }

    .unauthorized-card__content {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 2.5rem 2rem 2rem;
        text-align: center;
    }

    .unauthorized-card__icon {
        display: grid;
        width: 3rem;
        height: 3rem;
        place-items: center;
        margin-bottom: 1.25rem;
        border: 1px solid #fecaca;
        border-radius: 9999px;
        background: #fef2f2;
        color: #dc2626;
    }

    .dark .unauthorized-card__icon {
        border-color: rgb(127 29 29 / 0.65);
        background: rgb(69 10 10 / 0.35);
        color: #f87171;
    }

    .unauthorized-card__icon svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .unauthorized-card__badge {
        display: inline-flex;
        align-items: center;
        min-height: 1.5rem;
        margin-bottom: 0.75rem;
        padding: 0 0.625rem;
        border: 1px solid var(--border);
        border-radius: 9999px;
        background: color-mix(in srgb, var(--card) 88%, var(--foreground));
        color: var(--muted-foreground);
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.04em;
    }

    .unauthorized-card h1 {
        margin: 0;
        color: var(--foreground);
        font-size: 1.5rem;
        font-weight: 650;
        line-height: 2rem;
        letter-spacing: -0.025em;
    }

    .unauthorized-card p {
        max-width: 24rem;
        margin: 0.625rem 0 0;
        color: var(--muted-foreground);
        font-size: 0.875rem;
        line-height: 1.5rem;
    }

    .unauthorized-card__footer {
        display: flex;
        justify-content: center;
        gap: 0.75rem;
        padding: 1.25rem 2rem;
        border-top: 1px solid var(--border);
        background: color-mix(in srgb, var(--muted) 35%, transparent);
    }

    .unauthorized-card__button {
        display: inline-flex;
        height: 2.5rem;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0 1rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        background: var(--background);
        color: var(--foreground);
        font-size: 0.8125rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
    }

    .unauthorized-card__button:hover {
        background: var(--muted);
    }

    .unauthorized-card__button--primary {
        border-color: hsl(var(--primary));
        background: hsl(var(--primary));
        color: hsl(var(--primary-foreground));
    }

    .unauthorized-card__button--primary:hover {
        background: hsl(var(--primary) / 0.9);
    }

    .unauthorized-card__button svg {
        width: 1rem;
        height: 1rem;
    }

    @media (max-width: 520px) {
        .unauthorized-page {
            padding: 1rem 0;
        }

        .unauthorized-card__content {
            padding: 2rem 1.25rem 1.5rem;
        }

        .unauthorized-card__footer {
            flex-direction: column-reverse;
            padding: 1rem 1.25rem;
        }

        .unauthorized-card__button {
            width: 100%;
        }
    }
</style>

<section class="unauthorized-page" aria-labelledby="unauthorized-title">
    <div class="unauthorized-card">
        <div class="unauthorized-card__content">
            <div class="unauthorized-card__icon" aria-hidden="true">
                <i data-lucide="shield-alert"></i>
            </div>
            <span class="unauthorized-card__badge">HATA 403</span>
            <h1 id="unauthorized-title">Bu sayfaya erişiminiz yok</h1>
            <p>
                Bu işlemi gerçekleştirmek için gerekli kullanıcı yetkisine sahip değilsiniz.
                Erişime ihtiyacınız varsa hesap yöneticinizle iletişime geçin.
            </p>
        </div>

        <div class="unauthorized-card__footer">
            <button type="button" class="unauthorized-card__button" onclick="history.back()">
                <i data-lucide="arrow-left" aria-hidden="true"></i>
                Önceki Sayfa
            </button>
            <a href="dashboard" class="unauthorized-card__button unauthorized-card__button--primary">
                <i data-lucide="layout-dashboard" aria-hidden="true"></i>
                Kontrol Paneline Dön
            </a>
        </div>
    </div>
</section>
