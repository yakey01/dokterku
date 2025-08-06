import React, { useState, useEffect } from 'react';
import { 
  Star, 
  Trophy, 
  Crown, 
  Award, 
  Gift, 
  Coins, 
  Target, 
  TrendingUp, 
  Calendar, 
  DollarSign, 
  Zap, 
  Flame, 
  Heart, 
  Shield,
  Gem,
  Sparkles,
  ChevronRight,
  Clock,
  CheckCircle,
  Lock,
  Unlock,
  Medal,
  Diamond,
  Hexagon
} from 'lucide-react';

interface JaspelProps {
  userData?: any;
  onNavigate?: (tab: string) => void;
}

interface RewardTier {
  id: string;
  name: string;
  icon: React.ComponentType<any>;
  color: string;
  bgColor: string;
  borderColor: string;
  minAmount: number;
  bonusMultiplier: number;
}

interface JaspelRecord {
  id: string;
  month: string;
  baseAmount: number;
  bonusAmount: number;
  totalAmount: number;
  tier: string;
  status: 'pending' | 'approved' | 'paid';
  achievements: string[];
}

interface Milestone {
  id: string;
  title: string;
  description: string;
  target: number;
  current: number;
  reward: number;
  unlocked: boolean;
  icon: React.ComponentType<any>;
}

export function Jaspel({ userData, onNavigate }: JaspelProps) {
  const [currentTier, setCurrentTier] = useState<RewardTier>();
  const [totalEarnings, setTotalEarnings] = useState(48750000);
  const [currentMonthJaspel, setCurrentMonthJaspel] = useState(8750000);
  const [performanceBonus, setPerformanceBonus] = useState(1250000);
  const [rewardPoints, setRewardPoints] = useState(2847);
  const [selectedPeriod, setSelectedPeriod] = useState('current');

  const rewardTiers: RewardTier[] = [
    {
      id: 'bronze',
      name: 'Bronze Guardian',
      icon: Medal,
      color: 'text-amber-400',
      bgColor: 'from-amber-500/30 to-orange-500/30',
      borderColor: 'border-amber-400/50',
      minAmount: 0,
      bonusMultiplier: 1.0
    },
    {
      id: 'silver',
      name: 'Silver Warrior',
      icon: Award,
      color: 'text-gray-300',
      bgColor: 'from-gray-400/30 to-slate-500/30',
      borderColor: 'border-gray-400/50',
      minAmount: 5000000,
      bonusMultiplier: 1.15
    },
    {
      id: 'gold',
      name: 'Gold Champion',
      icon: Star,
      color: 'text-yellow-400',
      bgColor: 'from-yellow-500/30 to-amber-500/30',
      borderColor: 'border-yellow-400/50',
      minAmount: 8000000,
      bonusMultiplier: 1.3
    },
    {
      id: 'platinum',
      name: 'Platinum Elite', 
      icon: Diamond,
      color: 'text-cyan-300',
      bgColor: 'from-cyan-500/30 to-blue-500/30',
      borderColor: 'border-cyan-400/50',
      minAmount: 12000000,
      bonusMultiplier: 1.5
    },
    {
      id: 'legendary',
      name: 'Legendary Master',
      icon: Crown,
      color: 'text-purple-300',
      bgColor: 'from-purple-500/30 to-pink-500/30',
      borderColor: 'border-purple-400/50',
      minAmount: 20000000,
      bonusMultiplier: 2.0
    }
  ];

  const mockJaspelHistory: JaspelRecord[] = [
    {
      id: '1',
      month: 'Agustus 2024',
      baseAmount: 7500000,
      bonusAmount: 1250000,
      totalAmount: 8750000,
      tier: 'gold',
      status: 'pending',
      achievements: ['Perfect Attendance', 'Excellence Bonus', 'Team Leader']
    },
    {
      id: '2',
      month: 'Juli 2024',
      baseAmount: 8200000,
      bonusAmount: 800000,
      totalAmount: 9000000,
      tier: 'gold',
      status: 'paid',
      achievements: ['Performance Star', 'Quality Care']
    },
    {
      id: '3',
      month: 'Juni 2024',
      baseAmount: 7800000,
      bonusAmount: 650000,
      totalAmount: 8450000,
      tier: 'silver',
      status: 'paid',
      achievements: ['Consistent Performance']
    }
  ];

  const milestones: Milestone[] = [
    {
      id: '1',
      title: 'Monthly Champion',
      description: 'Achieve 10M+ Jaspel in a month',
      target: 10000000,
      current: 8750000,
      reward: 500000,
      unlocked: false,
      icon: Trophy
    },
    {
      id: '2',
      title: 'Attendance Master',
      description: 'Perfect attendance for 3 months',
      target: 3,
      current: 2,
      reward: 750000,
      unlocked: false,
      icon: Calendar
    },
    {
      id: '3',
      title: 'Excellence Streak',
      description: 'Maintain Gold tier for 6 months',
      target: 6,
      current: 4,
      reward: 1000000,
      unlocked: false,
      icon: Flame
    },
    {
      id: '4',
      title: 'Legendary Status',
      description: 'Reach 50M+ total earnings',
      target: 50000000,
      current: 48750000,
      reward: 2000000,
      unlocked: false,
      icon: Crown
    }
  ];

  useEffect(() => {
    // Determine current tier based on monthly jaspel
    const tier = rewardTiers
      .slice()
      .reverse()
      .find(t => currentMonthJaspel >= t.minAmount);
    setCurrentTier(tier);
  }, [currentMonthJaspel]);

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'paid':
        return <CheckCircle className="w-5 h-5 text-green-400" />;
      case 'approved':
        return <Clock className="w-5 h-5 text-blue-400" />;
      case 'pending':
        return <Clock className="w-5 h-5 text-yellow-400" />;
      default:
        return <Clock className="w-5 h-5 text-gray-400" />;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'paid':
        return 'from-green-500/30 to-emerald-500/30 border-green-400/30';
      case 'approved':
        return 'from-blue-500/30 to-cyan-500/30 border-blue-400/30';
      case 'pending':
        return 'from-yellow-500/30 to-amber-500/30 border-yellow-400/30';
      default:
        return 'from-gray-500/30 to-slate-500/30 border-gray-400/30';
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <div className="w-full min-h-screen relative overflow-y-auto">
        <div className="pb-32 lg:pb-16">
          <div className="max-w-sm mx-auto min-h-screen relative overflow-hidden">
        
        {/* Floating Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute top-20 left-8 w-40 h-40 bg-yellow-500/5 rounded-full blur-3xl animate-pulse"></div>
          <div className="absolute top-60 right-4 w-32 h-32 bg-purple-500/5 rounded-full blur-2xl animate-bounce"></div>
          <div className="absolute bottom-80 left-6 w-36 h-36 bg-pink-500/5 rounded-full blur-3xl animate-pulse"></div>
        </div>

        {/* Header */}
        <div className="px-6 pt-8 pb-6 relative z-10">
          <div className="text-center mb-6">
            <div className="flex items-center justify-center mb-4">
              <div className="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg border-2 border-yellow-400">
                <Trophy className="w-8 h-8 text-white" />
              </div>
            </div>
            <h1 className="text-3xl font-bold bg-gradient-to-r from-yellow-400 to-orange-400 bg-clip-text text-transparent mb-2">
              Rewards Vault
            </h1>
            <p className="text-yellow-200 text-lg">Performance Incentive System</p>
          </div>
        </div>

        {/* Current Tier Status */}
        <div className="px-6 mb-8 relative z-10">
          <div className="relative">
            <div className="absolute inset-0 bg-gradient-to-br from-yellow-600/30 via-orange-600/30 to-amber-600/30 rounded-3xl backdrop-blur-2xl"></div>
            <div className="absolute inset-0 bg-white/5 rounded-3xl border border-white/20"></div>
            <div className="relative p-8">
              
              {/* Current Tier Display */}
              <div className="text-center mb-6">
                <div className="flex items-center justify-center mb-4">
                  <div className="relative">
                    <div className={`w-20 h-20 bg-gradient-to-br ${currentTier?.bgColor || 'from-gray-500/30 to-slate-500/30'} rounded-2xl flex items-center justify-center shadow-lg border-2 ${currentTier?.borderColor || 'border-gray-400/50'}`}>
                      {currentTier?.icon && <currentTier.icon className={`w-10 h-10 ${currentTier.color}`} />}
                    </div>
                    <div className="absolute -top-3 -right-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-xs font-bold px-3 py-1 rounded-full border-2 border-white shadow-lg">
                      {currentTier?.bonusMultiplier}x
                    </div>
                  </div>
                </div>
                <h3 className={`text-2xl font-bold ${currentTier?.color || 'text-gray-400'} mb-2`}>
                  {currentTier?.name || 'No Tier'}
                </h3>
                <p className="text-gray-300 text-sm">
                  Bonus Multiplier: {currentTier?.bonusMultiplier}x
                </p>
              </div>

              {/* Monthly Jaspel Display */}
              <div className="mb-6">
                <div className="text-center mb-4">
                  <div className="text-4xl font-bold text-white mb-2">
                    {formatCurrency(currentMonthJaspel)}
                  </div>
                  <div className="text-yellow-300 text-sm">Jaspel Bulan Ini</div>
                </div>
                
                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-white/10">
                    <div className="flex items-center space-x-3 mb-2">
                      <DollarSign className="w-5 h-5 text-blue-400" />
                      <span className="text-white font-medium text-sm">Base</span>
                    </div>
                    <div className="text-blue-300 font-bold">
                      {formatCurrency(currentMonthJaspel - performanceBonus)}
                    </div>
                  </div>

                  <div className="bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-white/10">
                    <div className="flex items-center space-x-3 mb-2">
                      <Zap className="w-5 h-5 text-yellow-400" />
                      <span className="text-white font-medium text-sm">Bonus</span>
                    </div>
                    <div className="text-yellow-300 font-bold">
                      {formatCurrency(performanceBonus)}
                    </div>
                  </div>
                </div>
              </div>

              {/* Reward Points */}
              <div className="text-center">
                <div className="flex items-center justify-center space-x-2 mb-2">
                  <Gem className="w-6 h-6 text-purple-400" />
                  <span className="text-3xl font-bold text-white">{rewardPoints}</span>
                </div>
                <div className="text-purple-300 text-sm">Reward Points</div>
              </div>
            </div>
          </div>
        </div>

        {/* Achievement Milestones */}
        <div className="px-6 mb-8 relative z-10">
          <h3 className="text-xl font-bold mb-6 text-center bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
            Achievement Milestones
          </h3>

          <div className="space-y-4">
            {milestones.map((milestone) => (
              <div 
                key={milestone.id}
                className={`bg-white/5 backdrop-blur-2xl rounded-2xl p-4 border border-white/10 ${
                  milestone.current >= milestone.target ? 'ring-2 ring-green-400/50' : ''
                }`}
              >
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center space-x-3">
                    <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${
                      milestone.current >= milestone.target 
                        ? 'bg-gradient-to-br from-green-500 to-emerald-500' 
                        : 'bg-gradient-to-br from-gray-500 to-slate-600'
                    }`}>
                      <milestone.icon className="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <div className="text-white font-medium text-sm">{milestone.title}</div>
                      <div className="text-gray-300 text-xs">{milestone.description}</div>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="text-yellow-400 font-bold text-sm">
                      {formatCurrency(milestone.reward)}
                    </div>
                    <div className="text-xs text-gray-300">
                      {milestone.current >= milestone.target ? (
                        <span className="text-green-400">✓ Unlocked</span>
                      ) : (
                        `${milestone.current}/${milestone.target}`
                      )}
                    </div>
                  </div>
                </div>
                
                {/* Progress Bar */}
                <div className="w-full bg-gray-700/50 rounded-full h-2 overflow-hidden">
                  <div 
                    className={`h-2 rounded-full transition-all duration-1000 ${
                      milestone.current >= milestone.target 
                        ? 'bg-gradient-to-r from-green-500 to-emerald-500' 
                        : 'bg-gradient-to-r from-purple-500 to-pink-500'
                    }`}
                    style={{ width: `${Math.min((milestone.current / milestone.target) * 100, 100)}%` }}
                  ></div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Jaspel History */}
        <div className="px-6 relative z-10">
          <h3 className="text-xl font-bold mb-6 text-center bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">
            Reward History
          </h3>

          <div className="space-y-4">
            {mockJaspelHistory.map((record) => (
              <div 
                key={record.id}
                className={`bg-gradient-to-r ${getStatusColor(record.status)} rounded-2xl p-4 border backdrop-blur-sm`}
              >
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center space-x-3">
                    {getStatusIcon(record.status)}
                    <div>
                      <div className="text-white font-medium text-sm">{record.month}</div>
                      <div className="text-gray-300 text-xs capitalize">
                        {record.tier} tier • {record.status}
                      </div>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="text-white font-bold text-sm">
                      {formatCurrency(record.totalAmount)}
                    </div>
                    <div className="text-xs text-gray-300">
                      +{formatCurrency(record.bonusAmount)} bonus
                    </div>
                  </div>
                </div>

                {/* Achievements */}
                {record.achievements.length > 0 && (
                  <div className="flex flex-wrap gap-2 mt-3">
                    {record.achievements.map((achievement, index) => (
                      <div 
                        key={index}
                        className="bg-white/10 rounded-full px-3 py-1 text-xs text-yellow-300 font-medium"
                      >
                        ⭐ {achievement}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>

          {/* Summary Stats */}
          <div className="mt-8 bg-white/5 backdrop-blur-2xl rounded-2xl p-6 border border-white/10">
            <h4 className="text-lg font-bold text-center text-white mb-4">Career Summary</h4>
            <div className="grid grid-cols-2 gap-4">
              <div className="text-center">
                <div className="text-2xl font-bold text-green-400 mb-1">
                  {formatCurrency(totalEarnings)}
                </div>
                <div className="text-green-300 text-sm">Total Earnings</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-blue-400 mb-1">
                  {formatCurrency(totalEarnings / mockJaspelHistory.length)}
                </div>
                <div className="text-blue-300 text-sm">Average Monthly</div>
              </div>
            </div>
          </div>
        </div>
          </div>
        </div>
        {/* End of main content container */}
        
        {/* Medical RPG Bottom Navigation */}
      </div>
    </div>
  );
}