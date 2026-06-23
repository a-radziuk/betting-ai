import logging

import httpx

from config import Settings

logger = logging.getLogger(__name__)


class PlatformClient:
    def __init__(self, settings: Settings) -> None:
        self._settings = settings

    def request_registration_link(self, tg_id: int) -> str:
        response = httpx.post(
            self._settings.start_endpoint,
            json={"tg_id": tg_id},
            headers={
                "Authorization": f"Bearer {self._settings.api_secret}",
                "Accept": "application/json",
            },
            timeout=15.0,
        )

        if response.status_code == 422:
            detail = response.json()
            raise PlatformClientError(f"Invalid request: {detail}")

        if response.status_code == 401:
            raise PlatformClientError("Platform API rejected the bot credentials.")

        if response.status_code == 503:
            raise PlatformClientError("Telegram promobot API is not configured on the platform.")

        try:
            response.raise_for_status()
        except httpx.HTTPStatusError as exc:
            logger.exception("Platform API error for tg_id=%s", tg_id)
            raise PlatformClientError("Platform API returned an unexpected error.") from exc

        payload = response.json()
        link = payload.get("link")

        if not isinstance(link, str) or link.strip() == "":
            raise PlatformClientError("Platform API response did not include a registration link.")

        return link.strip()


class PlatformClientError(Exception):
    pass
