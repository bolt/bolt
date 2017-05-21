<?php

namespace Bolt\Provider;

use Bolt\EventListener\SwiftmailerListener;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Provider\SwiftmailerServiceProvider;
use Swift_Signers_SMimeSigner as SMimeSigner;
use Swift_Transport_Esmtp_AuthHandler as AuthHandler;
use Swift_Transport_EsmtpTransport as EsmtpTransport;
use Swift_Transport_FailoverTransport as FailoverTransport;
use Swift_Transport_SendmailTransport as SendmailTransport;

/**
 * SwiftMailer integration.
 *
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class MailerServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        if (!isset($app['swiftmailer.options'])) {
            $app->register(new SwiftmailerServiceProvider());
        }

        $app['swiftmailer.sendmailtransport'] = function ($app) {
            $localDomain = $app['config']->get('general/mailoptions/domain', '127.0.0.1');
            $sendmail = new SendmailTransport(
                $app['swiftmailer.transport.buffer'],
                $app['swiftmailer.transport.eventdispatcher'],
                $localDomain
            );

            return $sendmail;
        };

        $app['swiftmailer.transport'] = $app->extend(
            'swiftmailer.transport',
            function (EsmtpTransport $transport) use ($app) {
                /** @var array $options */
                $options = array_replace(array(
                    'host'       => 'localhost',
                    'port'       => 25,
                    'username'   => '',
                    'password'   => '',
                    'encryption' => null,
                    'auth_mode'  => null,
                ), (array) $app['config']->get('general/mailoptions'));

                $transport->setHost($options['host']);
                $transport->setPort($options['port']);
                $transport->setEncryption($options['encryption']);
                /** @var AuthHandler $transport */
                $transport->setUsername($options['username']);
                $transport->setPassword($options['password']);
                $transport->setAuthMode($options['auth_mode']);

                $app['swiftmailer.transport.eventdispatcher']->bindEventListener($app['swiftmailer.listener.send_performed']);

                $failover = new FailoverTransport();
                $failover->setTransports([$transport, $app['swiftmailer.sendmailtransport']]);

                return $failover;
            }
        );

        /** @see http://php.net/manual/en/openssl.ciphers.php */
        $app['swiftmailer.smime.cipher'] = OPENSSL_CIPHER_AES_256_CBC;
        $app['swiftmailer.smime.signer'] = function ($app) {
            $config = $app['config']->get('general/mailoptions/smime', []);
            $certPublic = $config['sign_cert_public'] ?? null;
            $certPrivate = $config['sign_cert_private'] ?? null;
            $certEncrypt = $config['encrypt_cert'] ?? null;
            $signer = new SMimeSigner();
            if ($certPublic) {
                $signer->setSignCertificate($certPublic, $certPrivate);
            }
            if ($certEncrypt) {
                $signer->setEncryptCertificate($certEncrypt, $app['swiftmailer.smime.cipher']);
            }

            return $signer;
        };

        $app['swiftmailer.listener.send_performed'] = function ($app) {
            return new SwiftmailerListener($app['swiftmailer.smime.signer']);
        };

        $app['swiftmailer.use_spool'] = function ($app) {
            return (bool) $app['config']->get('general/mailoptions/spool', true);
        };
    }
}
