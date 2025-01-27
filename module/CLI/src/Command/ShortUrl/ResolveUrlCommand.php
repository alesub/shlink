<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class ResolveUrlCommand extends Command
{
    public const NAME = 'short-url:parse';
    private const ALIASES = ['shortcode:parse', 'short-code:parse'];

    /** @var UrlShortenerInterface */
    private $urlShortener;

    public function __construct(UrlShortenerInterface $urlShortener)
    {
        parent::__construct();
        $this->urlShortener = $urlShortener;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setAliases(self::ALIASES)
            ->setDescription('Returns the long URL behind a short code')
            ->addArgument('shortCode', InputArgument::REQUIRED, 'The short code to parse')
            ->addOption('domain', 'd', InputOption::VALUE_REQUIRED, 'The domain to which the short URL is attached.');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $shortCode = $input->getArgument('shortCode');
        if (! empty($shortCode)) {
            return;
        }

        $io = new SymfonyStyle($input, $output);
        $shortCode = $io->ask('A short code was not provided. Which short code do you want to parse?');
        if (! empty($shortCode)) {
            $input->setArgument('shortCode', $shortCode);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $shortCode = $input->getArgument('shortCode');
        $domain = $input->getOption('domain');

        try {
            $url = $this->urlShortener->shortCodeToUrl($shortCode, $domain);
            $output->writeln(sprintf('Long URL: <info>%s</info>', $url->getLongUrl()));
            return ExitCodes::EXIT_SUCCESS;
        } catch (ShortUrlNotFoundException $e) {
            $io->error($e->getMessage());
            return ExitCodes::EXIT_FAILURE;
        }
    }
}
