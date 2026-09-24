<?php

namespace Linups\LinupsFirewall\Tests;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Linups\LinupsFirewall\Models\Keyword;

class NotFoundNotificationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('linups-config.notification_email', 'admin@example.test');
        $app['config']->set('linups-config.not_found_notification', true);
    }

    protected function defineRoutes($router): void
    {
        Route::get('/exists', fn () => 'ok');
    }

    private function sentMessages()
    {
        return $this->app['mailer']->getSymfonyTransport()->messages();
    }

    private function signedBanUrl(string $url = '/missing-page?a=1'): string
    {
        return url(URL::temporarySignedRoute('linups-firewall.ban-url', now()->addDay(), ['url' => $url], false));
    }

    private function banLinkFromMail(): string
    {
        $html = $this->sentMessages()->last()->getOriginalMessage()->getHtmlBody();
        preg_match('/href="([^"]+ban-url[^"]+)"/', $html, $m);
        $this->assertNotEmpty($m);

        return html_entity_decode($m[1]);
    }

    public function test_404_sends_mail_with_details_and_signed_ban_link(): void
    {
        $this->withHeaders(['User-Agent' => 'TestBot/1.0', 'Cookie' => 'session=secret-cookie'])
            ->get('/missing-page?a=1')
            ->assertNotFound();

        $this->assertCount(1, $this->sentMessages());
        $message = $this->sentMessages()->first()->getOriginalMessage();
        $html = $message->getHtmlBody();

        $this->assertStringContainsString('Page not found: /missing-page?a=1', $message->getSubject());
        $this->assertStringContainsString('http://localhost/missing-page?a=1', $html);
        $this->assertStringContainsString('TestBot/1.0', $html);
        $this->assertStringContainsString('127.0.0.1', $html);
        $this->assertStringContainsString('Add url to ban list', $html);
        $this->assertStringNotContainsString('secret-cookie', $html);

        $this->get($this->banLinkFromMail())->assertOk()->assertSee('value="/missing-page?a=1"', false);
    }

    public function test_ban_link_works_when_proxy_hides_https(): void
    {
        //--- Behind Cloudflare without trusted proxies: links are built as https, requests arrive as http
        URL::forceScheme('https');
        $this->get('/missing-page')->assertNotFound();
        URL::forceScheme('http');

        $link = $this->banLinkFromMail();
        $this->assertStringStartsWith('https://localhost/linups-firewall/ban-url?', $link);

        $this->get(str_replace('https://', 'http://', $link))->assertOk()->assertSee('value="/missing-page"', false);
    }

    public function test_existing_page_sends_no_mail(): void
    {
        $this->get('/exists')->assertOk();

        $this->assertCount(0, $this->sentMessages());
    }

    public function test_disabled_by_config(): void
    {
        config(['linups-config.not_found_notification' => false]);

        $this->get('/missing-page')->assertNotFound();

        $this->assertCount(0, $this->sentMessages());
    }

    public function test_same_url_is_reported_once_per_window(): void
    {
        $this->get('/missing-page')->assertNotFound();
        $this->get('/missing-page')->assertNotFound();
        $this->get('/other-missing-page')->assertNotFound();

        $this->assertCount(2, $this->sentMessages());
    }

    public function test_ban_form_rejects_unsigned_and_tampered_links(): void
    {
        $this->get('/linups-firewall/ban-url?url=/x')->assertForbidden();
        $this->get(str_replace('missing-page', 'other', $this->signedBanUrl()))->assertForbidden();
        $this->post('/linups-firewall/ban-url?url=/x', ['keyword' => 'wp-login'])->assertForbidden();

        $this->assertSame(0, Keyword::count());
    }

    public function test_ban_form_expires(): void
    {
        $url = $this->signedBanUrl();

        $this->travel(2)->days();

        $this->get($url)->assertForbidden();
    }

    public function test_ban_form_saves_edited_keyword(): void
    {
        $url = $this->signedBanUrl();

        //--- The redirect goes back to the (still validly signed) form with a status message
        $this->followingRedirects()
            ->post($url, ['keyword' => ' /missing-page '])
            ->assertOk()
            ->assertSee('added to the ban list', false);
        $this->followingRedirects()
            ->post($url, ['keyword' => '/missing-page'])
            ->assertOk()
            ->assertSee('already in the ban list', false);

        $this->assertSame(['/missing-page'], Keyword::pluck('keyword')->all());
    }

    public function test_ban_form_rejects_too_short_keyword(): void
    {
        $this->post($this->signedBanUrl(), ['keyword' => '/'])->assertSessionHasErrors('keyword');

        $this->assertSame(0, Keyword::count());
    }
}