<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPagesLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_register_and_forgot_password_are_localized_in_russian(): void
    {
        app()->setLocale('ru');
        config([
            'app.locale' => 'ru',
            'app.name' => 'BetAI',
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('BetAI | Вход', false)
            ->assertSee('Войдите, чтобы продолжить', false)
            ->assertSee('или продолжить с', false)
            ->assertSee('Продолжить с Google', false);

        $this->get('/register')
            ->assertOk()
            ->assertSee('BetAI | Регистрация', false)
            ->assertSee('Создайте аккаунт', false)
            ->assertSee('или зарегистрироваться через', false);

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('BetAI | Восстановление пароля', false)
            ->assertSee('Запросите ссылку', false)
            ->assertSee('Забыли пароль? Укажите email', false);
    }

    public function test_login_register_and_forgot_password_are_localized_in_georgian(): void
    {
        app()->setLocale('ge');
        config([
            'app.locale' => 'ge',
            'app.name' => 'BetAI',
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('BetAI | შესვლა', false)
            ->assertSee('შედით BetAI', false)
            ->assertSee('ან გააგრძელეთ', false)
            ->assertSee('Google-ით გაგრძელება', false);

        $this->get('/register')
            ->assertOk()
            ->assertSee('BetAI | რეგისტრაცია', false)
            ->assertSee('შექმენით ანგარიში', false)
            ->assertSee('ან დარეგისტრირდით', false);

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('BetAI | პაროლის აღდგენა', false)
            ->assertSee('მოითხოვეთ პაროლის', false)
            ->assertSee('დაგავიწყდათ პაროლი?', false);
    }
}
