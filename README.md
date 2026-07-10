# machinima-telegram-adapter

Telegram platform adapter for the Machinima core app.

Provides the platform-specific implementations of the core
`App\Contract\*` ports for the Telegram WebApp surface:

- `App\Contract\PlatformUiContext` → `Morfeditorial\MachinimaTelegramAdapter\PlatformUiContext\TelegramPlatformUiContext`
- `App\Contract\UserNotificationAddressResolver` → `Morfeditorial\MachinimaTelegramAdapter\Notification\TelegramNotificationAddressResolver`
- `App\Contract\NotificationChannelPort` → `Morfeditorial\MachinimaTelegramAdapter\Notification\TelegramNotificationService`
- `App\Event\UserAuthenticatedEvent` bridge from `Morfeditorial\TelegramBotBundle\Event\TelegramUserAuthenticatedEvent` → `Morfeditorial\MachinimaTelegramAdapter\EventListener\TelegramToIdentityBridge`

The adapter is loaded by `machinima-app` only when the active
`APP_PROFILE` is `telegram-webapp` or `telegram-bot`. Core profiles
(`core-only`) must not import any class from this package.
