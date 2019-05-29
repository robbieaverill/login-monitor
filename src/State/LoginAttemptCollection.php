<?php declare(strict_types=1);

namespace SilverStripe\LoginMonitor\State;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\LoginAttempt;

class LoginAttemptCollection
{
    use Injectable;

    /**
     * @var MemberLoginAttempt
     */
    private $attempts = [];

    /**
     * @var int
     */
    private $successful = 0;

    /**
     * @var int
     */
    private $failed = 0;

    /**
     * @param MemberLoginAttempt[] $attempts
     */
    public function __construct(array $attempts)
    {
        $this->attempts = $attempts;
        foreach ($attempts as $attempt) {
            if ($attempt->getSuccess()) {
                $this->successful++;
            } else {
                $this->failed++;
            }
        }
    }

    public static function fromDataList(DataList $list)
    {
        $attempts = [];
        foreach ($list as $loginAttempt) {
            /** @var LoginAttempt $loginAttempt */
            $succeeded = $loginAttempt->Status === 'Success';
            $attempt = MemberLoginAttempt::create($loginAttempt->MemberID, $loginAttempt->IP, $succeeded);
            $attempts[] = $attempt;
        }
        return new static($attempts);
    }

    /**
     * @param int $id
     * @return MemberLoginAttemptCollection
     */
    public function forMember(int $id): MemberLoginAttemptCollection
    {
        $attempts = [];
        foreach ($this->attempts as $attempt) {
            if ($attempt->getMemberID() === $id) {
                $attempts[] = $attempt;
            }
        }
        return MemberLoginAttemptCollection::create($attempts);
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return count($this->getAttempts());
    }

    /**
     * @return int
     */
    public function getSuccessful(): int
    {
        return $this->successful;
    }

    /**
     * @return int
     */
    public function getFailed(): int
    {
        return $this->failed;
    }

    /**
     * @return MemberLoginAttempt[]
     */
    public function getAttempts(): array
    {
        return $this->attempts;
    }

    /**
     * @return string[]
     */
    public function getIPs(): array
    {
        $ips = [];
        foreach ($this->getAttempts() as $attempt) {
            $ips[] = $attempt->getIP();
        }
        return array_unique($ips);
    }
}
