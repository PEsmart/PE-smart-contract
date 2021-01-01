<?php

namespace app\common\model;

use think\Model;

/**
 * 配置模型
 */
class Lang extends Model
{

    /**
     * 前端需要的语言数组
     * @return array
     */
    public static function language()
    {


        $lang =
            [

                //登录
                'Login'=>[
                    'email'                                              =>   __('Mailbox number'),
                    'pwd'                                                =>   __('Password'),
                    'login'                                              =>   __('Sign in'),
                    'register'                                           =>   __('Register'),
                    'found'                                              =>   __('Get back password'),
                ],
                //找回密码
                'found_pwd'=>[
                    'found'                                              =>   __('Get back password'),
                    'email'                                              =>   __('Please enter your mailbox'),
                    'code'                                               =>   __('Please enter the verification code'),
                    'login_pwd'                                          =>   __('Please enter your login password'),
                    'login_pwd_twice'                                    =>   __('Please enter your login password again'),
                    'submit'                                             =>   __('Submit'),

                ],
                //主页
                'Index'=>[
                    'head'                                               =>   __('AI-QuantAnt APP is an intellectualized APP service platform to meet the needs of C-end users for small assets value-added and preservation. The official version has been released and welcomed.'),
                    'more'                                               =>   __('More'),
                    'ac_plan'                                            =>   __('Merge business plan'),
                    'rd_plan'                                            =>   __('Research and development plan '),
                    'total'                                              =>   __('Open amount'),
                    'remaining'                                          =>   __('Remaining amount'),
                    'recovery'                                           =>   __('Repurchase date'),
                    'index'                                              =>   __('Home '),
                    'robot'                                              =>   __('Robot'),
                    'find'                                               =>   __('Discover'),
                    'my'                                                 =>   __('Me'),
                    'state'=>__('Status'),
                        'quick_exchange'                                 =>   __('Exchange'),
                        'buy_immediately'                                 =>   __('Buy'),
                        'data_from'                                 =>   __('Data source Huobi exchange, for reference only'),
                        'maximum'                                     =>__('Close  '),
                        'minimum'                                     =>__('Open'),
                        'max_change'                                     =>__('Maximum variation'),
                        'min_change'                                     =>__('Minimum variation'),

                ],
                //系统公告
                'system'=>[
                    'system'                                             =>   __('System announcement'),
                ],
                //系统详情
                'system_detail'=>[
                    'detail'                                             =>   __('Details of announcement'),
                    'content'                                            =>   __('Announcement Content'),
                ],
                //收并购计划
                'ac_plan'=>[
                    'introduce'                                          =>   __('Introduction to the Plan'),
                    'ac_plan'                                            =>   __('Merger business plan'),
                    'start_subscribing'                                           =>   __('Start subscribing'),
                ],
                //研发计划
                'rd_plan'=>[
                    'introduce'                                          =>   __('Introduction to the Plan'),
                    'rd_plan'                                            =>   __('Research and development plan '),
                    'start_subscribing'                                           =>   __('Start subscribing'),
                ],
                //机器人
                'robot'=>[
                    'list'                                               =>   __('Robot List'),
                    'model'                                              =>   __('Robot model'),
                    'work_state'                                         =>   __('Work status'),
                    'saturation_state'                                   =>   __('Load status'),
                    'introduce'                                          =>   __('Introduce'),
                    'daily_income'                                       =>   __('Average daily income'),
                    'residual_share'                                     =>   __('Remaining amount '),
                    'remaining_threads'                                  =>   __('Remaining threads'),
                    'comment'                                            =>   __('The remaining amount is the quantity that can be purchased, and the thread is the number of service order jobs'),
                    'start'                                              =>   __('Buy now'),
                    'purchase_condition'                                 =>   __('1. Please select the integer purchase according to the robot package price range;'),
                    'income_distribution'                                =>   __('2. The proceeds are issued at 1:00 am every day;'),
                    'start_up_fee'                                       =>   __('3. The robot startup fee is 10 dollars, which can be replaced by the equivalent CSQA;'),
                    'stop_contracts'                                     =>   __('4. The robot contract can be terminated at any time and terminated within the package period, deducting 5% handling fee and all proceeds obtained;'),
                    'lv_up'                                              =>   __('5. It can be recharged on the basis of the original package of the robot, and the next package will be upgraded automatically, and the package cycle will be reset;'),
                    'promise'                                            =>   __('6. CSQA promises that no matter what time, the robot will not make risky transactions, with the guarantee + stable income as the core indicator.'),
                    'usage_notice'=>__('User manual'),
                ],
                //购买
                'buy'=>[
                    'rent'                                               =>   __('Buy'),
                    'machine_name'                                       =>   __('Robot model'),
                    'input'                                              =>   __('Deposit amount'),
                    'dollar'                                             =>   __('Dollars'),
                    'cycle'                                              =>   __('Deposit plan'),
                    'placeholder'                                        =>   __('Please enter an integer'),
                    'day'                                                =>   __('Day'),
                    'total'                                              =>   __('Toatl amount'),
                    'method'                                             =>   __('Payment Way'),
                    'agree'                                              =>   __('I agree'),
                    'assets'                                             =>   __('Dollars  '),
                    'buy'                                                =>   __('Buy now'),
                    'terms_service'                                      =>   __('Terms service'),
                    'and'                                                =>   __('and'),
                    'privacy_clause'                                     =>   __('Privacy clause'),
                    'choose_amount_cycle'                                =>   __('Please choose the Deposit amount or Deposit plan'),
                    'check_agreement'                                    =>   __('Please confirm the agreement'),
                ],
                //发现
                'find'=>[

                    'find'                                               =>   __('Discover'),
                    'loan'                                               =>   __('Quantum trading'),
                    'light'                                              =>   __('Industrial system'),
                    'life'                                               =>   __('xNews system'),
                    'index'                                              =>   __('Home page'),
                    'robot'                                              =>   __('Robot'),
                    'my'                                                 =>   __('My'),
                    'expand'=>__('Click to expand'),
                    'close'=>__('Click to close'),
                        'user_area'=>__('Please use in mainland China')
                ],
                //正在开发中
                'deving'=>[
                    'functions'                                               =>   __('Functions to be developed'),
                    'process '                                               =>   __('In the process of development, please look forward to it...'),
                ],
                //注册
                'Register'=>[
                    'register'                                           =>   __('Register'),
                    'name'                                               =>   __('Full name'),
                    'email'                                              =>   __('Mailbox number'),
                    'pwd'                                                =>   __('Login password'),
                    'confirm_pwd'                                        =>   __('Confirm login password'),
                    'paypwd'                                             =>   __('Payment password'),
                    'confirm_paypwd'                                     =>   __('Confirm payment password'),
                    'tj'                                                 =>   __('Recommender number'),
                    'name_place'                                         =>   __('Please enter your name'),
                    'email_place'                                        =>   __('Please enter your mailbox'),
                    'pwd_place'                                          =>   __('Please enter your login password'),
                    'confirm_pwd_place'                                  =>   __('Please confirm the login password'),
                    'paypwd_place'                                       =>   __('Please enter the payment password'),
                    'confirm_paypwd_place'                               =>   __('Please confirm the payment password'),
                    'tj_place'                                           =>   __('Please enter a referee'),
                    'code'                                               =>   __('Verification Code'),
                    'code_place'                                         =>   __('Enter code'),
                    'click'                                              =>   __(' Get '),
                    'submit'                                             =>   __('Submit'),
                ],

                //我的主页
                'MyHome'=>[
                        'me'=>__('Me'),
                    'my_wallet'                                   =>  __('My Balances'),
                    'quantitative_assets'                         =>  __('Balances'),
                    'wallet_recharge'                             =>  __('Deposit'),
                    'quick_exchange'                              =>  __(' Exchange '),
                    'balance_withdrawal'                          =>  __('Withdraw'),
                    'my_robot'                                    =>  __('My Robot'),
                    'my_team'                                     =>  __('My Team'),
                    'my_assets'                                   =>  __('My Balances'),
                    'exclusive_customer_service'                  =>  __('Customer Service'),
                    'invite_good_friends'                         =>  __('Invite friends'),
                    'help_center'                                 =>  __('Help center'),
                    'about_us'                                    =>  __('About'),
                ],


                //个人中心
                'UserInfo'=>[
                    'nickname'                                    =>  __('Username '),
                    'change_password'                             =>  __('Change password'),
                    'payment_password'                            =>  __('Trade password'),
                    'cash_withdrawal_address'                     =>  __('Withdraw Address'),
                    'email'                                       =>  __('Email'),
                    'logout'                                      =>  __('Back'),
                    'personal_center'                             =>  __('Account center'),
                    'change_nickname'                             =>  __('Change Nickname'),
                    'save'                                        =>  __('Saved'),
                    'new_password'                                =>  __('New password'),
                    'old_password'                                =>  __('Old password'),
                    'confirm_password'                            =>  __('Confirm password'),
                    'enter_verification_code'                     =>  __('Please enter the verification code'),
                    'get_verification_code'                       =>  __('Get'),
                    'confirm_cash_withdrawal_address'             =>  __('Confirm cash withdrawal address'),
                    'enter_old_password'                          =>  __('Please enter old password'),
                    'enter_new_password'                          =>  __('Please enter new password'),
                    'enter_confirm_new_password'                  =>  __('Please enter new password again'),
                    'enter_cash_withdrawal_address'               =>  __('Please enter cash withdrawal address'),
                    'enter_confirm_cash_withdrawal_address'       =>  __('Please enter cash withdrawal address again'),
                        'numeric'=>__('6-12 digits (not all numbers or letters)'),
                        'verification_code'=>__('Verification code'),
                        'digits'=>__('New password'),
                        'has_been_set'=>__('Completed'),
                        'prompt'=>__('Note'),
                        'log_out'=>__('OK to log out'),
                        'determine'=>__('Done'),
                        'cancel'=>__('Cancel'),
                ],


                //提现
                'Withdrawal'=>[
                    'cash_withdrawal'                             =>  __('Withdrawal'),
                    'extraction_currency'                         =>  __('Withdrawal coin'),
                    'amount_to_be_withdrawal'                     =>  __('Available '),
                    'my_waller_address'                           =>  __('Withdraw Address'),
                    'withdrawal_amount'                           =>  __('Withdrawal amount'),
                    'actual_withdrawal_amount'                    =>  __('Actual withdrawal amount after deducting the fee'),
                    'enter_verification_code'                     =>  __('Enter code '),
                    'get_verification_code'                       =>  __('Get'),
                    'apply'                                       =>  __('Apply'),
                    'min_apply'                                   =>  __('Minimum withdrawal amount：'),
                    'service_charge'                              =>  __('Processing fee for withdrawal'),
                    'withdrawal_record'                           =>  __('Withdraw History'),
                    'rechargeable_wallet'                         =>  __('Balances '),
                    'btc_mount'                                   =>  __('Amount BTC'),
                    'verification_code'                           =>__('Verification code'),
                    'dollar'                                      =>__('Dollars'),
                    'enter_cash_withdrawal_address'               =>  __('Enter or paste address'),
                    'enter_withdrawal_amount'                     =>  __('Please enter_withdrawal_amount'),
                    'cash_date'=>__('Cash withdrawal date'),

                ],


                //提现记录
                'WithdrawalRecord'=>[
                    'withdrawal_record'                           =>  __('Withdrawal History'),
                    'rechargeable_wallet'                         =>  __('Rechargeable Wallet'),
                    'waller_address'                              =>  __('Address'),
                    
                    'amount'                                      =>  __('USD Amount'),

                    'status'                                      =>  __('Status'),
                    'date'                                        =>  __('Date'),
                ],

                //充值
                'Recharge'=>[
                    'recharge'                                    =>  __('Deposit'),
                    'recharge_record'                             =>  __('History'),
                    'my_wallet_qrcode'                            =>  __('QR Code'),
                    'notice'                                      =>  __('Please deposit from other exchanges')
                ],

                //充值记录
                'RechargeRecord'=>[
                    'recharge_record'                             =>  __('History'),
                    'rechargeable_wallet'                         =>  __('Rechargeable Wallet'),
                    'waller_address'                              =>  __('Address'),
                    'amount'                                      =>  __('USD Amount'),
                    'status'                                      =>  __('Status'),
                    'apply_date'                                        =>  __('Date'),
                ],
                //快速兑换
                'Exchange'=>[
                    'exchange'                                    =>  __('Exchange'),
                    'exchange_record'                             =>  __('Exchange History'),
                    'dollar_to_csqa'                              =>  __('Doller Exchange CSQA'),
                    'csqa_to_doller'                              =>  __('CSQA Exchange Doller'),
                    'dollar'                                      =>  __('Dollar'),
                    'enter_exchange_mount'                        =>  __('Please enter exchange mount'),
                    'auto_count_exchange'                         =>  __('Automatic Computational Convertibility'),
                    'confirm_exchange'                            =>  __('Exchange'),
                    'account_balance'                           =>  __('Balance'),
                    'enter_quantity'                           =>  __('Error'),
                ],
                //兑换记录
                'ExchangeRecord'=>[
                    'dollar_mount'                                =>  __('Total'),
                    'csqa_mount'                                  =>  __('CSQA mount'),
                    'exchange_time'                                        =>  __('Date '),
                    'dollar_to_csqa'                              =>  __('Dollar Exchange CSQA'),
                    'csqa_to_dollar'                              =>  __('CSQA Exchange Dollar'),
                ],

                'MyAssets'=>[
                    'my_assets'                                   =>  __('My Balances '),
                    'quantitative_assets'                         =>  __('Balances  '),
                    'promotion_benefits'                          =>  __('Invitation reward'),
                    'robot_model'                                 =>  __('Robot model'),
                    'Income_today'                                =>  __('Daily Return'),
                    'accumulated_income'                          =>  __('Total Return'),
                    'date'                                        =>  __('Date'),
                    'daily_quantity'                              =>  __('Daily quantity'),
                    'daily_value'                                 =>  __('Daily value'),
                    'source'                                      =>  __('Source'),
                    'total_robot_revenue'                         =>  __('Total Return'),
                    'the_total_number'                         =>  __('The total number'),
                ],

                'MyRobot'=>[
                    'my_robot'                                    =>__('My Robot'),
                    'total_robot_deposit'                         =>__('Total Robot Deposit'),
                    'renting_robot'                               =>__('Work Robots'),
                    'income'                                      =>__('Income'),
                    'daily_earnings'                              =>__('Daily Return '),
                    'general_deposit'                             =>__('Deposit '),
                    'rental_date'                                 =>__('Date   '),
                    'detail'                                      =>__('Detail'),

                    //机器人详情
                    'robot_detail'                                =>__('Detail '),
                    'rental_days'                                 =>__('Term Deposit'),
                    'rental_us_dollars'                           =>__('Deposit   '),
                    'way_of_issuing_coins'                        =>__('Way of issuing coins'),
                    'purchase_time'                               =>__('Date    '),
                    'adding_gold'                                 =>__('Add Deposit'),
                    'rent_withdrawal'                             =>__('Done   '),

                    //加金
                    'name_original_robot'                         =>__('Current Robot'),
                    'original_deposit'                            =>__('Current Deposit'),
                    'days_original_earnings'                      =>__('Current Deposit plan'),
                    'increase_amount'                             =>__('Add Deposit Amount'),
                    'transfer_information'                        =>__('Transfer information'),
                    'transfer_period'                             =>__('Transfer Term Deposit'),
                    'transfer_deposit'                            =>__('Transfer deposit'),
                    'maturity_time'                               =>__('Expire date'),
                    'price_difference'                            =>__('Deposit Amount'),
                    'be_careful'                                  =>__('Note: After added Deposit, the current robot revenue days are cleared, calculated according to the new robot.'),
                    'confirm_rent'                                =>__('Confirm rent'),
                    'cancel'                                      =>__('Cancel'),

                    'all'                                         =>__('All'),
                    'income'                                      =>__('Income'),
                    'finished'                                   =>__('Finished'),
                    'withdrawal' =>__('Release'),
                    'dollar'          =>  __('Dollar'),
                    'enter_int'                                      =>  __('Please enter an integer'),

                    'enter_amount'                                      =>  __('Enter Amount'),

                    'determine'=>__('Done'),

                    'currency_method'=>__('Settlement Method:daily settlement,settlement to your account the next day.'),
                ],
                //我的团队
                'MyTeam'=>[
                    'my_team'                                    =>__('My team'),
                    'my_team_number'                             =>__('Number of teams'),
                    'realname'                                   =>__('Name  '),
                    'email'                                      =>__('Mail'),
                    'rank'                                       =>__('Level'),
                    'Registration time'                          =>__('Registration time'),
                        'no_more'=>__('No More'),
                ],

                //专属客服

                'CustomerService'=>[
                    'customer_service'                           =>__('Customer service'),
                    'submitting_problem'                         =>__('Submit'),
                    'question_number'                            =>__('Number  '),
                    'tj_date'                                       =>__(' Date'),
                    'detail'                                     =>__('Detail'),
                    'view_question'                              =>__('View'),
                    'question_description'                       =>__('Question'),
                    'customer_service_reply'                     =>__('Reply'),
                    'no_reply'                     =>__('No reply'),
                        'enter_message'=>__('Please Enter Message')
                ],

                'Invite'=>[
                    'invite_friend'                             =>__('Invite friends'),
                    'tip'                                       =>__('Invite friends join AI-QuantAnt World'),
                    'from'=>__('from'),
                    'share'=>__('Share')
                ],

                'question'=>[

                    'common_problem'=>__('Question Type'),
                    'list'=>__('List of Questions'),
                    'details'=>__('Problem details'),
                ],
                'new'=>[
                    'announcement'=>__('No announcement'),
                    'plan'=>__('Common development plan'),
                    'candle'=>__('Candle'),
                    'l'=>__('L'),
                    'h'=>__('H'),
                    'difference'=>__('Difference'),
                    'choose'=>__('Please choose the Deposit amount or Deposit plan'),
                    'username'=>__('Username '),
                    'code'=>__('Verification code '),
                    'later'=>__('Please try again later'),
                    'success'=>__('success'),
                    'balances'=>__('Withdrawal coin: Balances'),
                    'minimum'=>__('Minimum withdrawal amount：'),
                    'fee'=>__('Fee：'),
                    'done'=>__('  Done'),
                    'next'=>__('Settlement Method: daily settlement, settlement to your account the next day'),
                    'note'=>__('Note: After added Deposit, the current robot revenue days are cleared, calculated according to the new robot'),
                    '_note'=>__('Note '),
                    '_done'=>__(' Done '),
                    'completed'=>__('completed'),
                    'cancelled'=>__('Cancelled'),
                    'member'=>__('Member'),
                    'quantitative'=>__('Quantitative Return'),
                    'csqa'=>__('CSQA Return'),
                    'share'=>__('From **** Share'),
                    'question'=>__('Question Type '),
                    'about'=>__('About Robot '),
                    'exchange '=>__('About Exchange   '),
                    'deposit'=>__('About Deposit'),
                    'withdraw'=>__('About Withdraw'),
                    'amount'=>__('Total Amount'),
                    'detail'=>__('Detail  '),
                    'change'=>__('Change'),
                    'value'=>__('Current Value'),
                    'startup'=>__('Startup fee'),
                    'exchange_amount'=>__('Exchange amount'),
                    'reward'=>__('Invitation reward'),
                ],
                'today'=>[
                    'source'=>__(' Detail '),
                    'address'=>__('Withdraw Address '),
                    'enter'=>__('Please enter address'),
                    'plan'=>__('Concentric Sharing Plan'),
                    'info'=>__('The latest Information'),
                    'completed'=>__('Completed'),
                    'differ'=>__('The two passwords differ'),
                    'confirm'=>__('Done'),
                    'sent'=>__('has been sent'),
                    'done' =>__('Done'),
                    'done_' =>__('   Done  '),
                    'cancel' =>__('Cancel'),
                    'people' =>__('Members'),
                    'open' =>__('Open '),
                    'close' =>__('Close'),
                ],
                'customer'=>[
                  'login'                      =>__('Login'),
                    'mail'                      =>__('Mail '),
                    'enter_e'                  =>__('Please enter mail'),
                    'password'                  =>__(' Login password'),
                     'enter_p'                 =>__('Please enter login password'),
                       'retrieve'                 =>__('Retrieve password'),
                    'register_now'                =>__(' Register '),
                    'register'              =>__('Register'),
                    'confirm'        =>__('Confirm password'),
                      'trade'      =>__(' Trade password'),
                      'recommender'  =>__('Recommender'),
                        'verification'=>__('Verification code'),
                       'get' =>__(' Get'),
                       'submit' =>__('Submit'),
                       'digits' =>__('6-12 digits（Contains number and letters）'),
                       'enter_d' =>__('Please enter 6 digits trading password'),
                       'enter_r' =>__('Please enter recommender’s number'),
                       'enter_v' =>__('Please enter verification code'),
                        'e_e'=>__('Please enter the correct email address'),
                        'e_p'=>__('Please enter the correct email address  or  passwords'),
                ],
                'copy'=>[
                   'copy'=>__('copy'),
                    's'=>__('Successful copy'),
                    'f'=>__('Failed  copy'),
                ]
            ];
        return $lang;
    }

}
