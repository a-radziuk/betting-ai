import { chromium } from 'playwright';

const tournamentUrl = process.argv[2];
const limit = Math.max(1, Number.parseInt(process.argv[3] ?? '20', 10) || 20);

if (!tournamentUrl) {
  console.error('Missing tournament URL argument');
  process.exit(1);
}

const MONTHS = {
  JAN: 0,
  FEB: 1,
  MAR: 2,
  APR: 3,
  MAY: 4,
  JUN: 5,
  JUL: 6,
  AUG: 7,
  SEP: 8,
  OCT: 9,
  NOV: 10,
  DEC: 11,
};

function isLiveStatus(status) {
  return /\bH[12]\b/.test(status);
}

function parseKickoffFromStatus(status) {
  const lines = status.split('\n').map((line) => line.trim()).filter(Boolean);
  if (lines.length < 2) {
    return null;
  }

  const dayLabel = lines[0].toUpperCase();
  const timeMatch = lines[1].match(/^(\d{1,2}):(\d{2})$/);
  if (!timeMatch) {
    return null;
  }

  const now = new Date();
  const kickoff = new Date(now);
  kickoff.setSeconds(0, 0);
  kickoff.setHours(Number.parseInt(timeMatch[1], 10), Number.parseInt(timeMatch[2], 10), 0, 0);

  if (dayLabel === 'TODAY') {
    if (kickoff < now) {
      kickoff.setDate(kickoff.getDate() + 1);
    }

    return kickoff.toISOString();
  }

  if (dayLabel === 'TOMORROW') {
    kickoff.setDate(kickoff.getDate() + 1);

    return kickoff.toISOString();
  }

  const monthMatch = dayLabel.match(/^([A-Z]{3})\s+(\d{1,2})$/);
  if (!monthMatch) {
    return null;
  }

  const month = MONTHS[monthMatch[1]];
  if (month === undefined) {
    return null;
  }

  kickoff.setMonth(month, Number.parseInt(monthMatch[2], 10));
  if (kickoff < now) {
    kickoff.setFullYear(kickoff.getFullYear() + 1);
  }

  return kickoff.toISOString();
}

function parseKickoffFromPrematch(dateText, timeText) {
  const dateLabel = (dateText || '').trim();
  const timeLabel = (timeText || '').trim();
  const timeMatch = timeLabel.match(/^(\d{1,2}):(\d{2})$/);

  if (!dateLabel || !timeMatch) {
    return null;
  }

  const hours = Number.parseInt(timeMatch[1], 10);
  const minutes = Number.parseInt(timeMatch[2], 10);
  const now = new Date();
  const kickoff = new Date(now);
  kickoff.setSeconds(0, 0);
  kickoff.setMilliseconds(0);
  kickoff.setHours(hours, minutes, 0, 0);

  const dayLabel = dateLabel.toUpperCase();

  if (dayLabel === 'TODAY') {
    if (kickoff < now) {
      kickoff.setDate(kickoff.getDate() + 1);
    }

    return kickoff.toISOString();
  }

  if (dayLabel === 'TOMORROW') {
    kickoff.setDate(kickoff.getDate() + 1);

    return kickoff.toISOString();
  }

  const dottedDateMatch = dateLabel.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
  if (dottedDateMatch) {
    return new Date(
      Number.parseInt(dottedDateMatch[3], 10),
      Number.parseInt(dottedDateMatch[2], 10) - 1,
      Number.parseInt(dottedDateMatch[1], 10),
      hours,
      minutes,
      0,
      0,
    ).toISOString();
  }

  const monthMatch = dayLabel.match(/^([A-Z]{3})\s+(\d{1,2})$/);
  if (monthMatch) {
    const month = MONTHS[monthMatch[1]];
    if (month === undefined) {
      return null;
    }

    kickoff.setMonth(month, Number.parseInt(monthMatch[2], 10));
    if (kickoff < now) {
      kickoff.setFullYear(kickoff.getFullYear() + 1);
    }

    return kickoff.toISOString();
  }

  return null;
}

async function extractPrematchKickoff(page) {
  return page.evaluate(() => {
    const date =
      document.querySelector('span#prematch-start-date, [data-testid="prematch-start-date"]')?.textContent?.trim() ??
      '';
    const time =
      document.querySelector('span#prematch-start-time, [data-testid="prematch-start-time"]')?.textContent?.trim() ??
      '';

    return { date, time };
  });
}

function parseKickoffFromTitle(title) {
  const match = title.match(/(\d{2})\.(\d{2})\.(\d{4}).*?(\d{1,2}):(\d{2})/);
  if (!match) {
    return null;
  }

  const day = Number.parseInt(match[1], 10);
  const month = Number.parseInt(match[2], 10) - 1;
  const year = Number.parseInt(match[3], 10);
  const hours = Number.parseInt(match[4], 10);
  const minutes = Number.parseInt(match[5], 10);

  return new Date(year, month, day, hours, minutes, 0, 0).toISOString();
}

async function expandAllShowMore(page) {
  for (let round = 0; round < 40; round += 1) {
    const clicked = await page.evaluate(() => {
      const norm = (value) => (value || '').replace(/\s+/g, ' ').trim();
      let count = 0;

      for (const block of document.querySelectorAll('.EC_Gz')) {
        const button = [...block.querySelectorAll('button, [role="button"]')].find((element) =>
          /^show more$/i.test(norm(element.textContent)),
        );

        if (!button) {
          continue;
        }

        button.click();
        count += 1;
      }

      return count;
    });

    if (clicked === 0) {
      break;
    }

    await page.waitForTimeout(350);
  }
}

async function extractLeagueEvents(page) {
  return page.evaluate(() => {
    const cards = [...document.querySelectorAll('[data-viewport-id]')];

    return cards.map((card) => {
      const id = card.getAttribute('data-viewport-id');
      const link = card.querySelector('a[href*="/events/"]');
      const status = card.querySelector('[data-testid="time-status"]')?.innerText?.trim() ?? '';
      const teamNodes = card.querySelectorAll('[data-testid^="competitor-"] span');
      const teams = [...teamNodes]
        .map((node) => node.textContent?.trim() ?? '')
        .filter((name) => name !== '');

      return {
        id,
        href: link?.href ?? null,
        status,
        home_team: teams[0] ?? '',
        away_team: teams[1] ?? '',
      };
    });
  });
}

const browser = await chromium.launch({
  headless: true,
  args: ['--disable-blink-features=AutomationControlled'],
});

try {
  const context = await browser.newContext({
    userAgent:
      'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
    viewport: { width: 1440, height: 2200 },
    locale: 'en-US',
  });

  const page = await context.newPage();
  await page.goto(tournamentUrl, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
  await page.waitForTimeout(5000);

  const leagueEvents = (await extractLeagueEvents(page))
    .filter((event) => event.id && event.href && event.home_team && event.away_team)
    .filter((event) => !isLiveStatus(event.status))
    .slice(0, limit);

  const events = [];

  for (const leagueEvent of leagueEvents) {
    await page.goto(leagueEvent.href, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
    await page.waitForTimeout(4000);
    await expandAllShowMore(page);

    const kickoffMeta = await extractPrematchKickoff(page);
    const title = await page.title();
    const startTime =
      parseKickoffFromPrematch(kickoffMeta.date, kickoffMeta.time) ??
      parseKickoffFromTitle(title) ??
      parseKickoffFromStatus(leagueEvent.status) ??
      new Date().toISOString();
    const markets = await page.evaluate(
      ({ homeTeam, awayTeam }) => {
        const norm = (value) => (value || '').replace(/\s+/g, ' ').trim();
        const homeTotalHeader = `${homeTeam} total`;
        const awayTotalHeader = `${awayTeam} total`;
        const homeScoreHeader = `${homeTeam} to score a goal`;
        const awayScoreHeader = `${awayTeam} to score a goal`;

        const findMarketContent = (marketTitle) => {
          const titleSpan = [...document.querySelectorAll('span.EC_HC')].find(
            (span) => norm(span.textContent) === marketTitle,
          );

          return titleSpan?.closest('.EC_Gz')?.querySelector('.EC_Fy') ?? null;
        };

        const parseOdds = (button) => {
          const raw = norm(button.querySelector('[data-id="odds-value"]')?.textContent);
          if (raw === '' || raw === '—') {
            return null;
          }

          const odds = Number.parseFloat(raw);

          return Number.isFinite(odds) ? odds : null;
        };

        const parseOutcomeButtons = (content) =>
          [...content.querySelectorAll('.EC_JA')].map((button) => {
            const odds = parseOdds(button);
            const label = norm(button.querySelector('.EC_JB')?.textContent);

            return { odds, label };
          });

        const parseFullTimeResult = (content) => {
          const selections = [];

          for (const { odds, label } of parseOutcomeButtons(content)) {
            if (odds === null || odds <= 1 || label === '') {
              continue;
            }

            let name = label.toUpperCase();
            if (label.toUpperCase() === homeTeam.toUpperCase() || label.toUpperCase().includes(homeTeam.toUpperCase())) {
              name = 'HOME';
            } else if (
              label.toUpperCase() === awayTeam.toUpperCase() ||
              label.toUpperCase().includes(awayTeam.toUpperCase())
            ) {
              name = 'AWAY';
            } else if (label.toUpperCase() === 'DRAW') {
              name = 'DRAW';
            }

            selections.push({
              external_id: name,
              name,
              odds,
              handicap: null,
            });
          }

          return selections.length === 3 ? selections : [];
        };

        const parseDoubleChance = (content) => {
          const selections = [];

          for (const { odds, label } of parseOutcomeButtons(content)) {
            if (odds === null || odds <= 1 || label === '') {
              continue;
            }

            const upper = label.toUpperCase();
            let name = upper;
            if (upper.includes(homeTeam.toUpperCase()) && upper.includes('DRAW')) {
              name = '1X';
            } else if (upper.includes(awayTeam.toUpperCase()) && upper.includes('DRAW')) {
              name = 'X2';
            } else if (upper === 'NO DRAW') {
              name = '12';
            }

            selections.push({
              external_id: name,
              name,
              odds,
              handicap: null,
            });
          }

          return selections.length >= 2 ? selections : [];
        };

        const parseYesNo = (content) => {
          const selections = [];

          for (const { odds, label } of parseOutcomeButtons(content)) {
            const upper = label.toUpperCase();
            if (odds === null || odds <= 1 || (upper !== 'YES' && upper !== 'NO')) {
              continue;
            }

            selections.push({
              external_id: upper,
              name: upper,
              odds,
              handicap: null,
            });
          }

          return selections.length === 2 ? selections : [];
        };

        const parseOverUnderRows = (content) => {
          const selections = [];

          for (const row of content.querySelectorAll('.EC_Ih')) {
            const spans = [...row.children].filter((child) => child.tagName === 'SPAN');
            if (spans.length < 3) {
              continue;
            }

            const lineSpan = spans.find((span) => span.classList.contains('EC_It')) ?? spans[1];
            const lineText = norm(lineSpan.textContent);
            if (!/^\d+(?:\.\d+)?$/.test(lineText)) {
              continue;
            }

            const lineValue = Number.parseFloat(lineText);
            const oddsButtons = spans.filter((span) => span.classList.contains('EC_JA'));
            if (oddsButtons.length < 2) {
              continue;
            }

            const overOdds = parseOdds(oddsButtons[0]);
            const underOdds = parseOdds(oddsButtons[1]);
            if (overOdds === null || underOdds === null || overOdds <= 1 || underOdds <= 1) {
              continue;
            }

            selections.push({
              external_id: `OVER_${lineValue}`,
              name: 'OVER',
              odds: overOdds,
              handicap: lineValue,
            });
            selections.push({
              external_id: `UNDER_${lineValue}`,
              name: 'UNDER',
              odds: underOdds,
              handicap: lineValue,
            });
          }

          return selections;
        };

        const parseHandicapRows = (content) => {
          const selections = [];

          for (const row of content.querySelectorAll('.EC_Ix')) {
            for (const button of row.querySelectorAll('.EC_JA')) {
              const odds = parseOdds(button);
              const label = norm(button.querySelector('.EC_JB')?.textContent);
              const handicapMatch = label.match(/\(([+-]?\d+(?:\.\d+)?)\)/);

              if (odds === null || odds <= 1 || handicapMatch === null) {
                continue;
              }

              const handicap = Number.parseFloat(handicapMatch[1]);
              const upper = label.toUpperCase();
              let name = 'HOME';

              if (upper.includes(awayTeam.toUpperCase())) {
                name = 'AWAY';
              } else if (upper.includes(homeTeam.toUpperCase())) {
                name = 'HOME';
              }

              selections.push({
                external_id: `${name}_${handicap}`,
                name,
                odds,
                handicap,
              });
            }
          }

          return selections;
        };

        const pushMarket = (marketList, externalId, type, selections) => {
          if (selections.length === 0) {
            return;
          }

          marketList.push({
            external_id: externalId,
            type,
            period: 'FT',
            line: null,
            selections,
          });
        };

        const marketList = [];

        const fullTimeContent = findMarketContent('Full-time result');
        if (fullTimeContent) {
          pushMarket(marketList, 'full-time-result', 'Full-time result', parseFullTimeResult(fullTimeContent));
        }

        const doubleChanceContent = findMarketContent('Double chance');
        if (doubleChanceContent) {
          pushMarket(marketList, 'double-chance', 'Double chance', parseDoubleChance(doubleChanceContent));
        }

        const totalContent = findMarketContent('Total');
        if (totalContent) {
          pushMarket(marketList, 'total', 'Total', parseOverUnderRows(totalContent));
        }

        const homeTotalContent = findMarketContent(homeTotalHeader);
        if (homeTotalContent) {
          pushMarket(marketList, 'home-total', homeTotalHeader, parseOverUnderRows(homeTotalContent));
        }

        const awayTotalContent = findMarketContent(awayTotalHeader);
        if (awayTotalContent) {
          pushMarket(marketList, 'away-total', awayTotalHeader, parseOverUnderRows(awayTotalContent));
        }

        const bttsContent = findMarketContent('Both teams to score');
        if (bttsContent) {
          pushMarket(marketList, 'btts', 'Both teams to score', parseYesNo(bttsContent));
        }

        const homeScoreContent = findMarketContent(homeScoreHeader);
        if (homeScoreContent) {
          pushMarket(marketList, 'home-to-score', homeScoreHeader, parseYesNo(homeScoreContent));
        }

        const awayScoreContent = findMarketContent(awayScoreHeader);
        if (awayScoreContent) {
          pushMarket(marketList, 'away-to-score', awayScoreHeader, parseYesNo(awayScoreContent));
        }

        const handicapContent = findMarketContent('Handicap');
        if (handicapContent) {
          pushMarket(marketList, 'handicap', 'Handicap', parseHandicapRows(handicapContent));
        }

        return marketList;
      },
      { homeTeam: leagueEvent.home_team, awayTeam: leagueEvent.away_team },
    );

    if (markets.length === 0) {
      continue;
    }

    events.push({
      external_id: leagueEvent.id,
      url: leagueEvent.href,
      home_team: leagueEvent.home_team,
      away_team: leagueEvent.away_team,
      start_time: startTime,
      markets,
    });
  }

  process.stdout.write(JSON.stringify({ events }));
} finally {
  await browser.close();
}
