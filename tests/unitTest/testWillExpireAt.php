<?php
 
namespace tests\unitTest\testWillExpireAt;
 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Mockery;
 
class testWillExpireAt extends TestCase
{
  public function testWillExpireAt()
    {
        // Mock the Carbon class
        $carbonMock = $this->getMockBuilder('Carbon\Carbon')
                           ->setMethods(['parse', 'diffInHours', 'addMinutes', 'addHours', 'subHours', 'format'])
                           ->getMock();

        // Set up the expected calls and return values for the mock
        $carbonMock->expects($this->at(0))
                   ->method('parse')
                   ->willReturn($carbonMock);

        $carbonMock->expects($this->at(1))
                   ->method('parse')
                   ->willReturn($carbonMock);

        $carbonMock->expects($this->once())
                   ->method('diffInHours')
                   ->willReturnOnConsecutiveCalls(26, 36, 60, 100); // Adjust the return values based on your test cases

        $carbonMock->expects($this->exactly(4))
                   ->method('format')
                   ->willReturnOnConsecutiveCalls(
                       '2024-02-23 12:00:00', // When $difference <= 90
                       '2024-02-22 11:30:00', // When $difference <= 24
                       '2024-02-23 02:00:00', // When $difference > 24 && $difference <= 72
                       '2024-02-20 12:00:00'  // When $difference > 72
                   );

        // Mock Carbon usage within the function
        Carbon::method('parse')->willReturn($carbonMock);

        // Call the function with mocked Carbon
        $result1 = TeHelper::willExpireAt('2024-02-23 12:00:00', '2024-02-22 10:00:00');
        $result2 = TeHelper::willExpireAt('2024-02-23 12:00:00', '2024-02-23 09:30:00');
        $result3 = TeHelper::willExpireAt('2024-02-23 12:00:00', '2024-02-22 14:00:00');
        $result4 = TeHelper::willExpireAt('2024-02-23 12:00:00', '2024-02-20 12:00:00');

        // Assert the results
        $this->assertEquals('2024-02-23 12:00:00', $result1);
        $this->assertEquals('2024-02-22 11:30:00', $result2);
        $this->assertEquals('2024-02-23 02:00:00', $result3);
        $this->assertEquals('2024-02-20 12:00:00', $result4);
    }

    public function testCreateOrUpdate()
    {
        // Mocking Carbon::now() and Carbon::parse()
        Carbon::shouldReceive('now')->andReturn(Carbon::parse('2022-01-01 00:00:00'));
        Carbon::shouldReceive('parse')->andReturnUsing(function ($value) {
            return Carbon::parse($value);
        });

        // Mocking User, Type, Company, Department, UserMeta, UsersBlacklist, UserLanguages, Town, and DB facade
        $userMock = Mockery::mock('alias:' . User::class);
        $typeMock = Mockery::mock('alias:' . Type::class);
        $companyMock = Mockery::mock('alias:' . Company::class);
        $departmentMock = Mockery::mock('alias:' . Department::class);
        $userMetaMock = Mockery::mock('alias:' . UserMeta::class);
        $usersBlacklistMock = Mockery::mock('alias:' . UsersBlacklist::class);
        $userLanguagesMock = Mockery::mock('alias:' . UserLanguages::class);
        $townMock = Mockery::mock('alias:' . Town::class);

        // Mocking methods on the User model
        $userMock->shouldReceive('findOrFail')->andReturnSelf();
        $userMock->shouldReceive('detachAllRoles');
        $userMock->shouldReceive('save');
        $userMock->shouldReceive('attachRole');
        $userMock->shouldReceive('enable')->andReturnSelf();
        $userMock->shouldReceive('disable')->andReturnSelf();
        $userMock->shouldReceive('id')->andReturn(1);

        // Mocking methods on the Type model
        $typeMock->shouldReceive('where')->andReturnSelf();
        $typeMock->shouldReceive('first')->andReturn((object)['id' => 1]);

        // Mocking methods on the Company model
        $companyMock->shouldReceive('create')->andReturn((object)['id' => 1]);

        // Mocking methods on the Department model
        $departmentMock->shouldReceive('create')->andReturn((object)['id' => 1]);

        // Mocking methods on the UserMeta model
        $userMetaMock->shouldReceive('firstOrCreate')->andReturnSelf();
        $userMetaMock->shouldReceive('toArray')->andReturn([]);
        $userMetaMock->shouldReceive('save');

        // Mocking methods on the UsersBlacklist model
        $usersBlacklistMock->shouldReceive('where')->andReturnSelf();
        $usersBlacklistMock->shouldReceive('get')->andReturn([]);
        $usersBlacklistMock->shouldReceive('pluck')->andReturn([]);

        // Mocking methods on the UserLanguages model
        $userLanguagesMock->shouldReceive('langExist')->andReturn(0);
        $userLanguagesMock->shouldReceive('deleteLang');

        // Mocking methods on the Town model
        $townMock->shouldReceive('save')->andReturnSelf();
        $townMock->shouldReceive('id')->andReturn(1);

        // Mocking DB facade
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('delete');

        // Request data
        $request = [
            'role' => 'some_role',
            // Add other request parameters here
        ];

        // Call the method
        $result = YourClass::createOrUpdate(null, $request);

        // Assertions
        $this->assertInstanceOf(User::class, $result);
    }

}