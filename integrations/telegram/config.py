import os
from dataclasses import dataclass

from dotenv import load_dotenv

load_dotenv()


@dataclass(frozen=True)
class Settings:
    bot_token: str
    platform_api_url: str
    api_secret: str

    @classmethod
    def from_env(cls) -> "Settings":
        bot_token = os.getenv("TELEGRAM_BOT_TOKEN", "").strip()
        platform_api_url = os.getenv("PLATFORM_APP_URL", "").strip().rstrip("/")
        api_secret = os.getenv("TELEGRAM_PROMOBOT_API_SECRET", "").strip()

        missing = [
            name
            for name, value in [
                ("TELEGRAM_BOT_TOKEN", bot_token),
                ("PLATFORM_APP_URL", platform_api_url),
                ("TELEGRAM_PROMOBOT_API_SECRET", api_secret),
            ]
            if value == ""
        ]

        if missing:
            raise RuntimeError(
                "Missing required environment variables: " + ", ".join(missing)
            )

        return cls(
            bot_token=bot_token,
            platform_api_url=platform_api_url,
            api_secret=api_secret,
        )

    @property
    def start_endpoint(self) -> str:
        return f"{self.platform_api_url}/api/telegram/start"
