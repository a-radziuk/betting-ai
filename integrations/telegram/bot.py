import logging

from telegram import Update
from telegram.ext import Application, CommandHandler, ContextTypes

from config import Settings
from platform_client import PlatformClient, PlatformClientError

logging.basicConfig(
    format="%(asctime)s %(levelname)s %(name)s %(message)s",
    level=logging.INFO,
)
logger = logging.getLogger(__name__)


async def start_command(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    message = update.effective_message
    user = update.effective_user

    if message is None or user is None:
        return

    client: PlatformClient = context.application.bot_data["platform_client"]

    try:
        client.request_registration_link(update.to_dict())
    except PlatformClientError as exc:
        logger.warning("Failed to create promocode for tg_id=%s: %s", user.id, exc)
        await message.reply_text(
            "Sorry, we could not prepare your access link right now. Please try again in a moment."
        )


def build_application(settings: Settings) -> Application:
    application = (
        Application.builder()
        .token(settings.bot_token)
        .build()
    )

    application.bot_data["platform_client"] = PlatformClient(settings)
    application.add_handler(CommandHandler("start", start_command))

    return application


def main() -> None:
    settings = Settings.from_env()
    application = build_application(settings)

    logger.info("Starting Telegram promobot (platform: %s)", settings.platform_api_url)
    application.run_polling(allowed_updates=Update.ALL_TYPES)


if __name__ == "__main__":
    main()
