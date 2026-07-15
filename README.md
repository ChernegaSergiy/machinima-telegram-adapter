<div align="center">

<img src="assets/images/morf-logo.svg" alt="MORF" width="320" />

# machinima-telegram-adapter

*Telegram platform adapter for the Machinima platform.*

---

</div>

Provides the platform-specific implementations of the core `Morfeditorial\MachinimaCoreBundle\Contract\*` ports for the Telegram WebApp surface:

- `Morfeditorial\MachinimaCoreBundle\Contract\PlatformAdapterInterface` → `Morfeditorial\MachinimaTelegramAdapter\TelegramPlatformAdapter`
  - bootstrap module (`Resources/public/js/telegram-bootstrap.js`) — client-side zero-click detection, posted to core's `/api/auth/bootstrap`
  - UI-hints module (`Resources/public/js/telegram-ui-hints.js`) — theme/back-button, only loaded for an already-authenticated Telegram session
- `Morfeditorial\MachinimaCoreBundle\Contract\IdentityProviderPort` (two, deliberately under different registry names — see `MiniApp\TelegramMiniAppIdentityProvider` docblock):
  - `telegram` → `Oidc\TelegramOidcProvider` (OAuth-button login, validates an id_token)
  - `telegram_mini_app` → `MiniApp\TelegramMiniAppIdentityProvider` (zero-click, validates raw Mini App `initData`; hidden from `/login` via `BootstrapOnlyIdentityProvider`)
- `Morfeditorial\MachinimaCoreBundle\Contract\UserNotificationAddressResolver` → `Morfeditorial\MachinimaTelegramAdapter\Notification\TelegramNotificationAddressResolver`
- `Morfeditorial\MachinimaCoreBundle\Contract\NotificationChannelPort` → `Morfeditorial\MachinimaTelegramAdapter\Notification\TelegramNotificationService`
- `Morfeditorial\MachinimaCoreBundle\Event\UserAuthenticatedEvent` bridge from `Morfeditorial\TelegramBotBundle\Event\TelegramUserAuthenticatedEvent` → `Morfeditorial\MachinimaTelegramAdapter\EventSubscriber\TelegramToIdentityBridge` (used by the Telegram *bot*, i.e. chat commands — unrelated to Mini App zero-click login)

The adapter is loaded by the host application only when the active `APP_PROFILE` requires Telegram capabilities (e.g. `telegram-webapp` or `telegram-bot`). Core profiles (`core-only`) must not import any class from this package.

## Env vars

- `TELEGRAM_BOT_TOKEN` — used both by `telegram-bot-bundle` and by `TelegramMiniAppIdentityProvider` to verify Mini App `initData` signatures.
- `TELEGRAM_OIDC_CLIENT_ID` / `TELEGRAM_OIDC_CLIENT_SECRET` / `TELEGRAM_OIDC_REDIRECT_URI` — OAuth-button login only.

## Contributing

Contributions are welcome and appreciated! Here's how you can contribute:

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Please make sure to update tests as appropriate and adhere to the existing coding style.

## License

This project is licensed under the CSSM Unlimited License v2.0 (CSSM-ULv2). See the [LICENSE](LICENSE) file for details.
