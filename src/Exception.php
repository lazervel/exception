<?php

declare(strict_types=1);

/**
 * Exception library managing multiple exceptions with CLI support for creating,
 * removing, and updating exceptions.
 * 
 * The (exception) Github repository
 * @see       https://github.com/lazervel/exception
 * 
 * @author    Shahzada Modassir <shahzadamodassir@gmail.com>
 * @author    Shahzadi Afsara   <shahzadiafsara@gmail.com>
 * 
 * @copyright (c) Shahzada Modassir
 * @copyright (c) Shahzadi Afsara
 * 
 * @license   MIT License
 * @see       https://github.com/lazervel/exception/blob/main/LICENSE
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Modassir\Exception;

class Exception
{
  private const CUSTOM_EXCEPTION_DIR = __DIR__.DIRECTORY_SEPARATOR.'Custom';
  private const CUSTOM_EXCEPTION_NAMESPACE = 'namespace Modassir\\Exception\\Custom;';
  private const CUSTOM_EXCEPTION_USE = 'use Modassir\\Exception\\Exception\\';

  private $secondryClass;
  private $primaryClass;
  private $executor;

  /**
   * 
   * @param string $executor      [required]
   * @param string $primaryClass  [required]
   * @param string $secondryClass [required]
   * 
   * @return void
   */
  public function __construct($executor, $primaryClass, $secondryClass)
  {
    $this->executor      = $executor;
    $this->primaryClass  = $primaryClass;
    $this->secondryClass = $secondryClass;
  }

  /**
   * @return string custom directory location.
   */
  private function CED() : string
  {
    return \defined('CUSTOM_EXCEPTION_DIR') ? CUSTOM_EXCEPTION_DIR : $this->CED();
  }

  /**
   * @param array $argv [required]
   * @return \Modassir\Exception\Exception
   */
  public static function argv(array $argv)
  {
    \array_shift($argv);

    $command = "-----------------------\n\n";
    $commands = [
      'Subclass:make CustomClass'   => 'Creates a new custom Exception class extends with subClass.',
      'CustomClass:rename NewClass' => 'Renamed existing custom Exception class.',
      'NewClass:remove'             => 'Removed existing custom Exception class.'
    ];

    $maxLenght = \strlen(\array_keys($commands)[1]);

    foreach($commands as $cmd => $desc) {
      $command .= \sprintf(
        "%s\x20 %s\n",
        self::format($cmd.\str_repeat(" ", $maxLenght - \strlen($cmd)), false),
        $desc
      );
    }

    $command .="\n";

    if ($argv === []) {
      echo self::format("Command required:\n\n", true);
      self::exit("Exception Command Help:\n%s", false, $command);
    }

    if (\count($argv) > 2) {
      self::exit(
        "\nError: Could not run command required #2 arguments but given [%s] extra.\n\n", true,
        \implode(',', \array_slice($argv, 2))
      );
    }

    $primary = \explode(':', $argv[0]);

    if (\count($primary) !== 2) {
      echo self::format( "\nError: Missing executor :target with [%s]", true, $argv[0]);
      self::exit("\n\nException Command Help:\n%s", false, $command);
    }

    $secondry = end($argv);

    return new self(\array_pop($primary), $primary[0], $secondry !== $argv[0] ? $secondry : null);
  }

  /**
   * @param string $exception [required]
   * @param string $newClass  [required]
   * 
   * @return void
   */
  public function rename(string $exception, string $newClass) : void
  {
    if (!self::exists($exception)) {
      self::exit(
        "\nError: Could not rename Exception [%s] not found.\n\n", true, $exception
      );
    }

    // Handle: Clousion Exception class.
    if ($exception === $newClass) {
      self::exit(
        "\nError: Could not rename Exception [%s] already renamed.\n\n", true,
        $exception
      );
    }

    $lines = @\file(($path = \sprintf('%s/%s.php', $this->CED(), $exception)));

    $matches_signature = \sprintf('class %s extends', $exception);

    foreach($lines as $i => $line) {
      if (\str_starts_with($line, $matches_signature)) {
        $lines[$i] = \str_replace($exception, $newClass, $line);
        break;
      }
    }

    @\file_put_contents($path, \join("", $lines));
    \rename($path, \sprintf('%s/%s.php', $this->CED(), $newClass));
    self::exit("\nSuccess: Exception [%s] has been renamed.\n\n", false, $newClass);
  }

  /**
   * @param string $subClass [required]
   * @param string $newClass [required]
   * 
   * @return void
   */
  public function make(string $subClass, string $newClass) : void
  {
    if (!\class_exists($subClass)) {
      self::exit("\nError: Could not create Exception Invalid subclass [%s].\n\n", true, $subClass);
    }

    if (($dir = $this->CED()) && !\is_dir($dir)) {
      \mkdir($dir);
    }

    if (self::exists($newClass)) {
      self::exit("\nError: Could not create Exception [%s] already exists.\n\n", true, $newClass);
    }

    $code = \sprintf("class %s extends %s\n{\n\t//\n}", $newClass, $subClass);
    $use = \sprintf("%s%s;", self::CUSTOM_EXCEPTION_USE, $subClass);

    $newException = \sprintf("<?php\n\ndeclare(strict_types=1);\n\n%s\n\n%s\n\n%s\n?>",
    self::CUSTOM_EXCEPTION_NAMESPACE, $use, $code);

    @\file_put_contents(\sprintf('%s/%s.php', $dir, $newClass), $newException);

    self::exit("\nSuccess: Exception %s subclass of %s created!\n\n", false, $newClass, $subClass);
  }

  private function exists(string $exception) : bool
  {
    return \file_exists(\sprintf('%s/%s.php', $this->CED(), $exception));
  }

  /**
   * @param string $exception [required]
   * @return void
   */
  public function remove(string $exception) : void
  {
    if ($this->secondryClass) {
      self::exit(
        "\nError: Could not remove Exception required #2 arguments but given #3 [%s].\n\n", true,
        $this->secondryClass
      );
    }

    if (self::exists($exception)) {
      \unlink(\sprintf('%s/%s.php', $this->CED(), $exception));
      self::exit("\nSuccess: Exception [%s] has been removed.\n\n", false, $exception);
    } else {
      self::exit("\nError: Could not remove Exception [%s] is not found.\n\n", true, $exception);
    }
  }

  /**
   * @param string $format  [required]
   * @param bool   $isError [required]
   * @param string $values  [required]
   * 
   * @return void
   */
  private static function exit(string $format, bool $isError = true, ...$values) : void
  {
    exit(self::format($format, $isError, ...$values));
  }

  /**
   * @param string $format  [required]
   * @param bool   $isError [required]
   * @param string $values  [required]
   * 
   * @return string formated adjusted string with colored terminal show
   */
  private static function format(string $format, bool $isError = true, ...$values) : string
  {
    $cmdColor = $isError ? '197;15;31' : '19;161;14';
    return \sprintf(\sprintf("\033[38;2;%sm%s\033[0m", $cmdColor, $format), ...$values);
  }

  /**
   * 
   */
  public function run() : void
  {
    if (!\method_exists($this, $this->executor)) {
      self::exit("\nError: Could not run command invalid executor [%s].\n\n", true, $this->executor);
    }

    \call_user_func([$this, $this->executor], $this->primaryClass, $this->secondryClass);
  }
}
?>
