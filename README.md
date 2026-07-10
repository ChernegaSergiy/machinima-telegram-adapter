# machinima-telegram-adapter

Telegram platform adapter for the Machinima core app.

Provides the platform-specific implementations of the core
`App\Contract\*` ports for the Telegram WebApp surface:

- `App\Contract\PlatformAdapterInterface` → `Morfeditorial\MachinimaTelegramAdapter\TelegramPlatformAdapter`
  - bootstrap module (`Resources/public/js/telegram-bootstrap.js`) — client-side zero-click detection, posted to core's `/api/auth/bootstrap`
  - UI-hints module (`Resources/public/js/telegram-ui-hints.js`) — theme/back-button, only loaded for an already-authenticated Telegram session
- `App\Contract\IdentityProviderPort` (two, deliberately under different registry names — see `MiniApp\TelegramMiniAppIdentityProvider` docblock):
  - `telegram` → `Oidc\TelegramOidcProvider` (OAuth-button login, validates an id_token)
  - `telegram_mini_app` → `MiniApp\TelegramMiniAppIdentityProvider` (zero-click, validates raw Mini App `initData`; hidden from `/login` via `BootstrapOnlyIdentityProvider`)
- `App\Contract\UserNotificationAddressResolver` → `Morfeditorial\MachinimaTelegramAdapter\Notification\TelegramNotificationAddressResolver`
- `App\Contract\NotificationChannelPort` → `Morfeditorial\MachinimaTelegramAdapter\Notification\TelegramNotificationService`
- `App\Event\UserAuthenticatedEvent` bridge from `Morfeditorial\TelegramBotBundle\Event\TelegramUserAuthenticatedEvent` → `Morfeditorial\MachinimaTelegramAdapter\EventSubscriber\TelegramToIdentityBridge` (used by the Telegram *bot*, i.e. chat commands — unrelated to Mini App zero-click login)

The adapter is loaded by `machinima-app` only when the active
`APP_PROFILE` is `telegram-webapp` or `telegram-bot`. Core profiles
(`core-only`) must not import any class from this package.

## Env vars

- `TELEGRAM_BOT_TOKEN` — used both by `telegram-bot-bundle` and by
  `TelegramMiniAppIdentityProvider` to verify Mini App `initData` signatures.
- `TELEGRAM_OIDC_CLIENT_ID` / `TELEGRAM_OIDC_CLIENT_SECRET` / `TELEGRAM_OIDC_REDIRECT_URI`
  — OAuth-button login only.

## Note on `telegram_tma` firewall authenticator

`config/profiles/telegram-webapp/packages/security.yaml` in `machinima-app`
still enables `telegram_tma: true`, the zero-click firewall authenticator
from `telegram-bot-bundle`. As of this refactor it is redundant: the client
no longer sends the header that authenticator reads (see
`AuthBootstrapController` in `machinima-app` instead). It's left in place
here because touching that bundle's own wiring is outside this package's
source — worth removing once confirmed nothing else depends on it.
