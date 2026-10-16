<?php

declare(strict_types=1);

namespace Gubee\Integration\Command;

use Gubee\Integration\Api\Data\ConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function explode;
use function in_array;
use function sprintf;
use function str_replace;
use function substr;

abstract class AbstractCommand extends Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    protected InputInterface $input;
    protected ManagerInterface $eventDispatcher;
    protected LoggerInterface $logger;
    protected OutputInterface $output;
    protected ConfigInterface $configManager;

    /**
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     * @throws LogicException When the command name is empty.
     */
    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        ConfigInterface $configManager,
        ?string $name = null
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->logger          = $logger;
        $this->configManager = $configManager;
        parent::__construct($name);
    }

    /**
     * Runs the command.
     *
     * The code to execute is either defined directly with the
     * setCode() method or by overriding the execute() method
     * in a sub-class.
     *
     * @see setCode()
     * @see execute()
     *
     * @return int The command exit code.
     * @throws ExceptionInterface When input binding fails. Bypass this by calling {@link ignoreValidationErrors()}.
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;
        $this->getEventDispatcher()->dispatch(
            $this->normalizeEventName(
                sprintf(
                    "gubee.command.%s.run.before",
                    $this->getName()
                )
            ),
            [
                'command' => $this,
                'input'   => $input,
                'output'  => $output,
            ]
        );

        $result = parent::run($input, $output);
        $this->getEventDispatcher()->dispatch(
            $this->normalizeEventName(
                sprintf(
                    "gubee.command.%s.run.after",
                    $this->getName()
                )
            ),
            [
                'command' => $this,
                'input'   => $input,
                'output'  => $output,
                'result'  => $result,
            ]
        );

        return $result;
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @see setCode()
     * @see execute()
     *
     * @return int 0 if everything went fine, or an exit code
     * @throws LogicException When this abstract method is not implemented.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input  = $input;
        $this->getLogger()->info(
            sprintf(
                "Running command '%s'",
                $this->getName()
            )
        );
        $this->getEventDispatcher()->dispatch(
            $this->normalizeEventName("gubee.command.execute.before"),
            [
                'command' => $this,
                'input'   => $input,
                'output'  => $output,
            ]
        );

        if (! $this->validateExecution()) {
            $this->getLogger()->error(
                sprintf(
                    "Command '%s' is not enabled for execution",
                    $this->getName()
                )
            );
            return self::FAILURE;
        }

        $this->getEventDispatcher()->dispatch(
            $this->normalizeEventName(
                sprintf(
                    "gubee.command.%s.execute.before",
                    $this->getName()
                )
            ),
            [
                'command' => $this,
                'input'   => $input,
                'output'  => $output,
            ]
        );

        $result = $this->doExecute();

        $this->getEventDispatcher()->dispatch(
            $this->normalizeEventName(
                sprintf(
                    "gubee.command.%s.execute.after",
                    $this->getName()
                )
            ),
            [
                'command' => $this,
                'input'   => $input,
                'output'  => $output,
                'result'  => $result,
            ]
        );

        $this->getEventDispatcher()->dispatch(
            $this->normalizeEventName("gubee.command.execute.after"),
            [
                'command' => $this,
                'input'   => $input,
                'output'  => $output,
                'result'  => $result,
            ]
        );

        return $result;
    }

    /**
     * Executes the command.
     */
    abstract protected function doExecute(): int;

    /**
     * Sets the name of the command.
     *
     * This method can set both the namespace and the name if
     * you separate them by a colon (:)
     *
     *     $command->setName('foo:bar');
     *
     * @param string $name The command name
     * @return $this
     * @throws InvalidArgumentException When the name is invalid.
     */
    public function setName(string $name)
    {
        return parent::setName(
            sprintf(
                '%s:%s',
                'gubee',
                $name
            )
        );
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getEventDispatcher(): ManagerInterface
    {
        return $this->eventDispatcher;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    protected function normalizeEventName(string $name)
    {
        // replace any non-alphanumeric characters with a dash
        return str_replace(
            [":", "."],
            "_",
            $name
        );
    }

    public function getPriority(): int
    {
        return 0;
    }

    /**
     * Check if the entity being processed is enabled for syncing.
     */
    public function validateExecution(): bool
    {
        $name = $this->getName();
        if ($name === null) {
            return true;
        }

        // Strip the 'gubee:' prefix
        $name = substr($name, strlen('gubee:'));
        $parts = explode(':', $name);

        if (count($parts) < 2) {
            return true;
        }

        $entityMap = [
            'catalog' => [
                'product'  => 'product',
                'category' => 'category',
            ],
            'sales' => [
                'order' => 'order',
            ],
        ];

        $entity = $entityMap[$parts[0]][$parts[1]] ?? null;
        if ($entity === null) {
            return true;
        }

        return in_array($entity, $this->configManager->getSyncEntities(), true);
    }
}
