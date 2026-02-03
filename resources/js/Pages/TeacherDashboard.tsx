import React, { useState, useEffect } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link } from '@inertiajs/react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { apiLogger } from '@/utils/logger';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Progress } from '@/Components/ui/progress';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { formatNumber, calculateTrend, type TrendData } from '@/lib/utils';
import {
  BookOpen,
  Users,
  CheckCircle,
  AlertTriangle,
  TrendingUp,
  TrendingDown,
  Calendar,
  UserPlus,
  UserCheck,
  UserX,
  BarChart3,
  Search,
  Mail,
  Phone,
  Award,
  MessageSquare,
  Settings,
  Plus,
  Eye,
  Edit,
  Clock,
  GraduationCap,
  Target,
  Activity,
  XCircle,
  LayoutGrid,
  List,
  Table as TableIcon,
  MapPin
} from 'lucide-react';

interface Teacher {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  specialization?: string;
  experience_years?: number;
}

interface TrainingClass {
  id: string;
  training_id: number;
  date: string;
  start_time: string;
  end_time: string;
  room: string;
  max_students: number;
  enrolled_count: number;
  training: {
    id: number;
    title: string;
    level: string;
  };
}

interface Student {
  id: number;
  name: string;
  email: string;
  phone?: string;
  avatar?: string;
  training_id?: number;
  training_title?: string;
  training_class_id?: number;
  enrollment: {
    progress: number;
    grade: number;
    attendance_rate: number;
    status: string;
  };
}

interface Formation {
  id: string;
  uuid: string;
  title: string;
  level: string;
  classes_count: number;
  students_count: number;
  completion_rate: number;
  average_grade: number;
  attendance_rate: number;
  trend: 'up' | 'down' | 'stable';
}

interface AttendanceRecord {
  date: string;
  present: number;
  absent: number;
  rate: number;
}

interface EvaluationResult {
  id: string;
  title: string;
  average: number;
  passed: number;
  failed: number;
  completion_date: string;
}

interface RecentActivity {
  id: number;
  action: string;
  student: string;
  studentId: number;
  time: string;
  type: 'success' | 'info' | 'warning';
}

interface Props {
  auth: {
    user: Teacher;
  };
  classes?: TrainingClass[];
  upcomingClasses?: TrainingClass[];
  totalStudents?: number;
  averageProgress?: number;
  averageAttendance?: number;
  atRiskStudents?: number;
  students?: Student[];
  formations?: Formation[];
  recentActivities?: RecentActivity[];
  evaluations?: any[];
  attendanceData?: any[];
  previousPeriodStats?: {
    totalStudents?: number;
    averageAttendance?: number;
    atRiskStudents?: number;
  };
}

const TeacherDashboard: React.FC<Props> = ({
  auth,
  classes = [],
  upcomingClasses = [],
  totalStudents = 0,
  averageProgress = 0,
  averageAttendance = 0,
  atRiskStudents = 0,
  students = [],
  formations = [],
  recentActivities = [],
  evaluations = [],
  attendanceData = [],
  previousPeriodStats
}) => {
  const [activeTab, setActiveTab] = useState('overview');
  const [selectedClass, setSelectedClass] = useState('all');
  const [selectedStudent, setSelectedStudent] = useState<Student | null>(null);
  const [showStudentModal, setShowStudentModal] = useState(false);
  const [showAddStudentModal, setShowAddStudentModal] = useState(false);
  const [showAttendanceModal, setShowAttendanceModal] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [searchFormations, setSearchFormations] = useState('');
  const [filterStatus, setFilterStatus] = useState<'all' | 'active' | 'at-risk'>('all');
  const [viewMode, setViewMode] = useState<'grid' | 'list' | 'table'>('grid');
  const [formationsViewMode, setFormationsViewMode] = useState<'grid' | 'list' | 'table'>('grid');

  // Calculer les trends dynamiquement
  const studentsTrend = calculateTrend(totalStudents, previousPeriodStats?.totalStudents);
  const attendanceTrend = calculateTrend(averageAttendance, previousPeriodStats?.averageAttendance, true);
  const atRiskTrend = calculateTrend(atRiskStudents, previousPeriodStats?.atRiskStudents);

  // Filter students by class
  const filteredStudents = students.filter((student) => {
    const matchesSearch = student.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         student.email.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesClass = selectedClass === 'all' || student.training_class_id?.toString() === selectedClass;
    const matchesStatus = filterStatus === 'all' || student.enrollment.status === filterStatus;
    return matchesSearch && matchesClass && matchesStatus;
  });

  // Filter formations
  const filteredFormations = formations.filter((formation) => {
    return formation.title.toLowerCase().includes(searchFormations.toLowerCase());
  });

  // Get unique classes for filter
  const uniqueClasses = Array.from(new Set(students.map(s => s.training_class_id).filter(Boolean)));

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'excellent': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 border-green-200';
      case 'good': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 border-blue-200';
      case 'average': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 border-yellow-200';
      case 'at-risk': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 border-red-200';
      default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border-gray-200';
    }
  };

  const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
      excellent: 'Excellent',
      good: 'Bon',
      average: 'Moyen',
      'at-risk': 'En difficulté',
      active: 'Actif'
    };
    return labels[status] || status;
  };

  const getTrendIcon = (trend: string) => {
    return trend === 'up' ? <TrendingUp className="h-4 w-4 text-green-600 dark:text-green-400" /> :
           trend === 'down' ? <TrendingDown className="h-4 w-4 text-red-600 dark:text-red-400" /> :
           <div className="h-4 w-4 bg-gray-400 rounded-full" />;
  };

  return (
    <DashboardLayout
      title={`Espace Enseignant${auth.user.experience_years ? ` • ${auth.user.experience_years} ans d'expérience` : ''}`}
      description={auth.user.specialization || 'Tableau de bord enseignant'}
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <Button variant="outline" className="border-gray-300 dark:border-gray-600 px-2 sm:px-4">
            <MessageSquare className="h-4 w-4 sm:mr-2" />
            <span className="hidden sm:inline">Messages</span>
          </Button>
          <Button className="bg-primary hover:bg-primary shadow-lg px-2 sm:px-4">
            <Settings className="h-4 w-4 sm:mr-2" />
            <span className="hidden sm:inline">Paramètres</span>
          </Button>
        </div>
      }
    >
      <Head title="Espace Enseignant" />

      <div className="space-y-6 bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen -m-4 p-4">

        {/* Statistiques avec animations */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          {[
            { label: 'Étudiants', value: totalStudents, icon: Users, color: 'blue', trend: studentsTrend.formatted },
            { label: 'Classes', value: classes.length, icon: BookOpen, color: 'green', trend: 'Toutes actives' },
            { label: 'Assiduité', value: `${formatNumber(averageAttendance)}%`, icon: UserCheck, color: 'yellow', trend: attendanceTrend.formatted },
            { label: 'Étudiants à risque', value: atRiskStudents, icon: AlertTriangle, color: 'red', trend: atRiskTrend.formatted }
          ].map((stat, index) => (
            <Card key={index} className="group hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-0 shadow-lg dark:bg-gray-800">
              <CardContent className="p-6">
                <div className="flex items-center gap-4">
                  <div className={`p-4 rounded-2xl bg-${stat.color}-100 dark:bg-${stat.color}-900/30 group-hover:bg-${stat.color}-200 dark:group-hover:bg-${stat.color}-800/50 transition-colors`}>
                    <stat.icon className={`h-8 w-8 text-${stat.color}-600 dark:text-${stat.color}-400`} />
                  </div>
                  <div className="flex-1">
                    <p className="text-sm text-gray-600 dark:text-gray-400 font-medium">{stat.label}</p>
                    <p className="text-3xl font-bold text-gray-900 dark:text-white mb-1">{stat.value}</p>
                    <p className="text-xs text-gray-500 dark:text-gray-500">{stat.trend}</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Navigation améliorée */}
        <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
          <div className="bg-white dark:bg-gray-800 rounded-2xl p-2 shadow-lg border border-gray-100 dark:border-gray-700">
            <TabsList className="grid w-full grid-cols-5 bg-transparent gap-2">
              {[
                { id: 'overview', label: 'Vue d\'ensemble', icon: BarChart3 },
                { id: 'formations', label: 'Formations', icon: BookOpen },
                { id: 'students', label: 'Étudiants', icon: Users },
                { id: 'attendance', label: 'Présences', icon: UserCheck },
                { id: 'evaluations', label: 'Évaluations', icon: GraduationCap }
              ].map((tab) => (
                <TabsTrigger
                  key={tab.id}
                  value={tab.id}
                  className="flex items-center gap-2 px-6 py-3 rounded-xl data-[state=active]:bg-primary data-[state=active]:text-white data-[state=active]:shadow-lg transition-all"
                >
                  <tab.icon className="h-4 w-4" />
                  <span className="hidden sm:inline font-medium">{tab.label}</span>
                </TabsTrigger>
              ))}
            </TabsList>
          </div>

          {/* Vue d'ensemble */}
          <TabsContent value="overview" className="space-y-6">
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <div className="lg:col-span-2">
                <Card className="shadow-lg border-0 dark:bg-gray-800">
                  <CardHeader className="pb-4">
                    <CardTitle className="flex items-center gap-2 text-xl">
                      <Target className="h-6 w-6 text-primary dark:text-blue-400" />
                      Mes formations
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      {formations.map((formation) => (
                        <div
                          key={formation.id}
                          className="border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-blue-300 dark:hover:border-primary hover:shadow-md transition-all duration-200 cursor-pointer"
                          onClick={() => window.location.href = route('trainings.show', formation.uuid)}
                        >
                          <div className="flex items-center justify-between mb-4">
                            <div className="flex-1">
                              <h3 className="font-bold text-lg text-gray-900 dark:text-white hover:text-primary dark:hover:text-blue-400 transition-colors">{formation.title}</h3>
                              <div className="flex items-center gap-3 mt-2">
                                <Badge className="bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 border-purple-200 dark:border-purple-700">
                                  {formation.level}
                                </Badge>
                                <span className="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                                  <Users className="h-4 w-4" />
                                  {formation.classes_count} classe(s) • {formation.students_count} étudiants
                                </span>
                              </div>
                            </div>
                            <div className="flex items-center gap-2">
                              {getTrendIcon(formation.trend)}
                              <Button
                                variant="outline"
                                size="sm"
                                className="shadow-sm"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  window.location.href = route('trainings.show', formation.uuid);
                                }}
                              >
                                <Eye className="h-4 w-4 mr-2" />
                                Voir
                              </Button>
                            </div>
                          </div>

                          <div className="grid grid-cols-3 gap-6">
                            <div>
                              <div className="text-sm text-gray-600 dark:text-gray-400 mb-2">Progression</div>
                              <div className="flex items-center gap-3">
                                <Progress value={formation.completion_rate} className="flex-1 h-3" />
                                <span className="font-bold text-lg dark:text-white">{formatNumber(formation.completion_rate)}%</span>
                              </div>
                            </div>
                            <div className="text-center">
                              <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Note moyenne</div>
                              <div className="text-2xl font-bold text-green-600 dark:text-green-400">{formatNumber(formation.average_grade)}/100</div>
                            </div>
                            <div className="text-center">
                              <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">Assiduité</div>
                              <div className="text-2xl font-bold text-primary dark:text-blue-400">{formatNumber(formation.attendance_rate)}%</div>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              </div>

              <div className="space-y-6">
                <Card className="shadow-lg border-0 dark:bg-gray-800">
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Activity className="h-5 w-5 text-green-600 dark:text-green-400" />
                      Activité récente
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      {recentActivities.map((activity) => (
                        <div
                          key={activity.id}
                          className="group flex items-start gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                          onClick={() => {
                            const student = students.find(s => s.id === activity.studentId);
                            if (student) {
                              setSelectedStudent(student);
                              setShowStudentModal(true);
                            }
                          }}
                        >
                          <div className={`w-2 h-2 rounded-full mt-2 ${
                            activity.type === 'success' ? 'bg-green-500' :
                            activity.type === 'info' ? 'bg-primary' : 'bg-yellow-500'
                          }`}></div>
                          <div className="flex-1">
                            <p className="text-sm font-medium dark:text-white">{activity.action}</p>
                            <p className="text-sm text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-blue-400 transition-colors">{activity.student}</p>
                            <p className="text-xs text-gray-500 dark:text-gray-500 mt-1">{activity.time}</p>
                          </div>
                          <Eye className="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>

                {upcomingClasses.length > 0 && (
                  <Card className="shadow-lg border-0 dark:bg-gray-800">
                    <CardHeader>
                      <CardTitle className="flex items-center gap-2">
                        <Clock className="h-5 w-5 text-orange-600 dark:text-orange-400" />
                        Cours à venir
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div className="space-y-3">
                        {upcomingClasses.slice(0, 2).map((classItem, index) => (
                          <div key={index} className={`flex items-center justify-between p-4 rounded-xl border-l-4 ${
                            index === 0 ? 'bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-l-blue-500' : 'bg-gray-50 dark:bg-gray-700/50'
                          }`}>
                            <div>
                              <div className={`font-bold ${index === 0 ? 'text-blue-900 dark:text-blue-300' : 'dark:text-white'}`}>
                                {classItem.training.title}
                              </div>
                              <div className={`text-sm ${index === 0 ? 'text-primary dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'}`}>
                                {index === 0 ? 'Aujourd\'hui' : 'Demain'} {classItem.start_time}-{classItem.end_time}
                              </div>
                            </div>
                            <Badge className={index === 0 ? 'bg-primary text-white' : 'dark:bg-gray-600'}>
                              {classItem.room}
                            </Badge>
                          </div>
                        ))}
                      </div>
                    </CardContent>
                  </Card>
                )}
              </div>
            </div>
          </TabsContent>

          {/* Onglet Formations */}
          <TabsContent value="formations" className="space-y-6">
            <Card className="shadow-lg border-0 dark:bg-gray-800">
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="flex items-center gap-2">
                    <BookOpen className="h-6 w-6 text-primary dark:text-blue-400" />
                    Gestion des formations
                  </CardTitle>
                  <div className="flex gap-3">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                      <Input
                        type="text"
                        placeholder="Rechercher..."
                        value={searchFormations}
                        onChange={(e) => setSearchFormations(e.target.value)}
                        className="pl-10 w-64"
                      />
                    </div>
                    <div className="flex gap-1 border rounded-lg p-1 bg-gray-50 dark:bg-gray-700">
                      <Button
                        variant={formationsViewMode === 'grid' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setFormationsViewMode('grid')}
                        className="h-8 w-8 p-0"
                      >
                        <LayoutGrid className="h-4 w-4" />
                      </Button>
                      <Button
                        variant={formationsViewMode === 'list' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setFormationsViewMode('list')}
                        className="h-8 w-8 p-0"
                      >
                        <List className="h-4 w-4" />
                      </Button>
                      <Button
                        variant={formationsViewMode === 'table' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setFormationsViewMode('table')}
                        className="h-8 w-8 p-0"
                      >
                        <TableIcon className="h-4 w-4" />
                      </Button>
                    </div>
                    <Button onClick={() => window.location.href = route('trainings.create')} className="bg-green-600 hover:bg-green-700">
                      <Plus className="h-4 w-4 mr-2" />
                      Ajouter
                    </Button>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {formationsViewMode === 'grid' && (
                  <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    {filteredFormations.map((formation) => (
                      <Card
                        key={formation.id}
                        className="hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 cursor-pointer border-l-4 border-l-primary dark:bg-gray-700"
                        onClick={() => window.location.href = route('trainings.show', formation.uuid)}
                      >
                        <CardContent className="p-5">
                          <div className="mb-4">
                            <h3 className="font-bold text-lg dark:text-white mb-2">{formation.title}</h3>
                            <Badge className="bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                              {formation.level}
                            </Badge>
                          </div>

                          <div className="grid grid-cols-2 gap-3 mb-4">
                            <div className="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                              <p className="text-sm text-gray-600 dark:text-gray-400">Classes</p>
                              <p className="text-xl font-bold text-blue-600 dark:text-blue-400">{formation.classes_count}</p>
                            </div>
                            <div className="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                              <p className="text-sm text-gray-600 dark:text-gray-400">Étudiants</p>
                              <p className="text-xl font-bold text-green-600 dark:text-green-400">{formation.students_count}</p>
                            </div>
                          </div>

                          <div className="space-y-2">
                            <div>
                              <div className="flex justify-between text-sm mb-1">
                                <span className="text-gray-600 dark:text-gray-400">Progression</span>
                                <span className="font-semibold dark:text-white">{formatNumber(formation.completion_rate)}%</span>
                              </div>
                              <Progress value={formation.completion_rate} className="h-2" />
                            </div>
                            <div className="flex justify-between items-center pt-2">
                              <span className="text-sm text-gray-600 dark:text-gray-400">Note moyenne</span>
                              <span className="font-bold text-lg text-green-600 dark:text-green-400">{formatNumber(formation.average_grade)}/100</span>
                            </div>
                            <div className="flex justify-between items-center">
                              <span className="text-sm text-gray-600 dark:text-gray-400">Assiduité</span>
                              <span className="font-bold text-lg text-primary dark:text-blue-400">{formatNumber(formation.attendance_rate)}%</span>
                            </div>
                          </div>
                        </CardContent>
                      </Card>
                    ))}
                  </div>
                )}

                {formationsViewMode === 'list' && (
                  <div className="space-y-3">
                    {filteredFormations.map((formation) => (
                      <Card
                        key={formation.id}
                        className="hover:shadow-lg transition-all cursor-pointer border-l-4 border-l-primary dark:bg-gray-700"
                        onClick={() => window.location.href = route('trainings.show', formation.uuid)}
                      >
                        <CardContent className="p-4">
                          <div className="flex items-center gap-4">
                            <div className="flex-1 grid grid-cols-5 gap-4 items-center">
                              <div className="col-span-2">
                                <h3 className="font-bold dark:text-white">{formation.title}</h3>
                                <Badge className="mt-1 bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                  {formation.level}
                                </Badge>
                              </div>
                              <div className="text-center">
                                <p className="text-sm text-gray-600 dark:text-gray-400">Classes</p>
                                <p className="font-bold text-lg text-blue-600 dark:text-blue-400">{formation.classes_count}</p>
                              </div>
                              <div className="text-center">
                                <p className="text-sm text-gray-600 dark:text-gray-400">Étudiants</p>
                                <p className="font-bold text-lg text-green-600 dark:text-green-400">{formation.students_count}</p>
                              </div>
                              <div className="text-center">
                                <p className="text-sm text-gray-600 dark:text-gray-400">Progression</p>
                                <p className="font-bold text-lg dark:text-white">{formatNumber(formation.completion_rate)}%</p>
                              </div>
                            </div>
                          </div>
                        </CardContent>
                      </Card>
                    ))}
                  </div>
                )}

                {formationsViewMode === 'table' && (
                  <div className="overflow-x-auto">
                    <table className="w-full">
                      <thead className="border-b dark:border-gray-700">
                        <tr>
                          <th className="text-left py-3 px-4 font-semibold dark:text-white">Formation</th>
                          <th className="text-center py-3 px-4 font-semibold dark:text-white">Niveau</th>
                          <th className="text-center py-3 px-4 font-semibold dark:text-white">Classes</th>
                          <th className="text-center py-3 px-4 font-semibold dark:text-white">Étudiants</th>
                          <th className="text-center py-3 px-4 font-semibold dark:text-white">Progression</th>
                          <th className="text-center py-3 px-4 font-semibold dark:text-white">Note moy.</th>
                          <th className="text-center py-3 px-4 font-semibold dark:text-white">Assiduité</th>
                          <th className="text-center py-3 px-4 font-semibold dark:text-white">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {filteredFormations.map((formation) => (
                          <tr key={formation.id} className="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td className="py-3 px-4">
                              <Link
                                href={route('trainings.show', formation.uuid)}
                                className="font-bold dark:text-white hover:text-primary dark:hover:text-blue-400 hover:underline transition-colors"
                              >
                                {formation.title}
                              </Link>
                            </td>
                            <td className="py-3 px-4 text-center">
                              <Badge className="bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                {formation.level}
                              </Badge>
                            </td>
                            <td className="py-3 px-4 text-center">
                              <span className="font-bold text-blue-600 dark:text-blue-400">{formation.classes_count}</span>
                            </td>
                            <td className="py-3 px-4 text-center">
                              <span className="font-bold text-green-600 dark:text-green-400">{formation.students_count}</span>
                            </td>
                            <td className="py-3 px-4 text-center">
                              <div className="flex items-center gap-2 justify-center">
                                <Progress value={formation.completion_rate} className="w-20 h-2" />
                                <span className="font-semibold dark:text-white">{formatNumber(formation.completion_rate)}%</span>
                              </div>
                            </td>
                            <td className="py-3 px-4 text-center">
                              <span className="font-bold text-green-600 dark:text-green-400">{formatNumber(formation.average_grade)}/100</span>
                            </td>
                            <td className="py-3 px-4 text-center">
                              <span className="font-bold text-primary dark:text-blue-400">{formatNumber(formation.attendance_rate)}%</span>
                            </td>
                            <td className="py-3 px-4 text-center">
                              <Button
                                size="sm"
                                variant="ghost"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  window.location.href = route('trainings.show', formation.uuid);
                                }}
                              >
                                <Eye className="h-4 w-4 mr-1" />
                                Voir
                              </Button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Onglet Étudiants */}
          <TabsContent value="students" className="space-y-6">
            <Card className="shadow-lg border-0 dark:bg-gray-800">
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="flex items-center gap-2">
                    <Users className="h-6 w-6 text-primary dark:text-blue-400" />
                    Gestion des étudiants
                  </CardTitle>
                  <div className="flex gap-3">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                      <Input
                        type="text"
                        placeholder="Rechercher..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="pl-10 w-64"
                      />
                    </div>
                    <Select value={selectedClass} onValueChange={setSelectedClass}>
                      <SelectTrigger className="w-48">
                        <SelectValue placeholder="Filtrer par classe" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">Toutes les classes</SelectItem>
                        {classes.filter(c => c.training).map((classItem) => (
                          <SelectItem key={classItem.id} value={classItem.id.toString()}>
                            {classItem.training.title} - {classItem.room}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <div className="flex gap-1 border rounded-lg p-1 bg-gray-50 dark:bg-gray-700">
                      <Button
                        variant={viewMode === 'grid' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setViewMode('grid')}
                        className="h-8 w-8 p-0"
                      >
                        <LayoutGrid className="h-4 w-4" />
                      </Button>
                      <Button
                        variant={viewMode === 'list' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setViewMode('list')}
                        className="h-8 w-8 p-0"
                      >
                        <List className="h-4 w-4" />
                      </Button>
                      <Button
                        variant={viewMode === 'table' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => setViewMode('table')}
                        className="h-8 w-8 p-0"
                      >
                        <TableIcon className="h-4 w-4" />
                      </Button>
                    </div>
                    <Button onClick={() => setShowAddStudentModal(true)} className="bg-green-600 hover:bg-green-700">
                      <Plus className="h-4 w-4 mr-2" />
                      Ajouter
                    </Button>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {viewMode === 'grid' && (
                  <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                  {filteredStudents.map((student) => (
                    <Card
                      key={student.id}
                      className="hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 cursor-pointer border-l-4 border-l-gray-200 dark:border-l-gray-700 hover:border-l-blue-500 dark:bg-gray-700"
                      onClick={() => {
                        setSelectedStudent(student);
                        setShowStudentModal(true);
                      }}
                    >
                      <CardContent className="p-5">
                        <div className="flex items-center gap-3 mb-4">
                          <Avatar className="h-12 w-12">
                            <AvatarImage
                              src={student.avatar ? `/storage/${student.avatar}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(student.name)}`}
                              alt={student.name}
                            />
                            <AvatarFallback className="bg-primary text-white font-bold">
                              {student.name.split(' ').map(n => n[0]).join('')}
                            </AvatarFallback>
                          </Avatar>
                          <div className="flex-1">
                            <h3 className="font-bold text-lg dark:text-white">{student.name}</h3>
                            <p className="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                              <Mail className="h-3 w-3" />
                              {student.email}
                            </p>
                          </div>
                          <Badge className={`${getStatusColor(student.enrollment.status)} font-medium`}>
                            {getStatusLabel(student.enrollment.status)}
                          </Badge>
                        </div>

                        <div className="space-y-3">
                          <div className="flex justify-between items-center">
                            <span className="text-sm text-gray-600 dark:text-gray-400">Note moyenne</span>
                            <span className="font-bold text-lg dark:text-white">{formatNumber(student.enrollment.grade)}/20</span>
                          </div>

                          <div>
                            <div className="flex justify-between items-center mb-1">
                              <span className="text-sm text-gray-600 dark:text-gray-400">Assiduité</span>
                              <span className="font-bold dark:text-white">{formatNumber(student.enrollment.attendance_rate)}%</span>
                            </div>
                            <Progress value={student.enrollment.attendance_rate} className="h-2" />
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  ))}
                  </div>
                )}

                {viewMode === 'list' && (
                  <div className="space-y-3">
                    {filteredStudents.map((student) => (
                      <Card
                        key={student.id}
                        className="hover:shadow-lg transition-all cursor-pointer border-l-4 border-l-gray-200 dark:border-l-gray-700 hover:border-l-blue-500 dark:bg-gray-700"
                        onClick={() => {
                          setSelectedStudent(student);
                          setShowStudentModal(true);
                        }}
                      >
                        <CardContent className="p-4">
                          <div className="flex items-center gap-4">
                            <Avatar className="h-12 w-12">
                              <AvatarImage
                                src={student.avatar ? `/storage/${student.avatar}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(student.name)}`}
                                alt={student.name}
                              />
                              <AvatarFallback className="bg-primary text-white font-bold">
                                {student.name.split(' ').map(n => n[0]).join('')}
                              </AvatarFallback>
                            </Avatar>
                            <div className="flex-1 grid grid-cols-4 gap-4 items-center">
                              <div>
                                <h3 className="font-bold dark:text-white">{student.name}</h3>
                                <p className="text-sm text-gray-600 dark:text-gray-400">{student.email}</p>
                              </div>
                              <div className="text-center">
                                <p className="text-sm text-gray-600 dark:text-gray-400">Note moyenne</p>
                                <p className="font-bold text-lg dark:text-white">{formatNumber(student.enrollment.grade)}/20</p>
                              </div>
                              <div className="text-center">
                                <p className="text-sm text-gray-600 dark:text-gray-400">Assiduité</p>
                                <p className="font-bold text-lg dark:text-white">{formatNumber(student.enrollment.attendance_rate)}%</p>
                              </div>
                              <div className="text-right">
                                <Badge className={`${getStatusColor(student.enrollment.status)} font-medium`}>
                                  {getStatusLabel(student.enrollment.status)}
                                </Badge>
                              </div>
                            </div>
                          </div>
                        </CardContent>
                      </Card>
                    ))}
                  </div>
                )}

                {viewMode === 'table' && (
                  <div className="overflow-x-auto">
                    <table className="w-full">
                      <thead className="border-b dark:border-gray-700">
                        <tr>
                          <th className="text-left py-3 px-4 font-semibold dark:text-white">Étudiant</th>
                          <th className="text-left py-3 px-4 font-semibold dark:text-white">Email</th>
                          <th className="text-center py-3 px-4 font-semibold dark:text-white">Note</th>
                          <th className="text-center py-3 px-4 font-semibold dark:text-white">Assiduité</th>
                          <th className="text-center py-3 px-4 font-semibold dark:text-white">Statut</th>
                          <th className="text-center py-3 px-4 font-semibold dark:text-white">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {filteredStudents.map((student) => (
                          <tr key={student.id} className="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td className="py-3 px-4">
                              <div className="flex items-center gap-3">
                                <Avatar className="h-10 w-10">
                                  <AvatarImage
                                    src={student.avatar ? `/storage/${student.avatar}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(student.name)}`}
                                    alt={student.name}
                                  />
                                  <AvatarFallback className="bg-primary text-white font-bold text-sm">
                                    {student.name.split(' ').map(n => n[0]).join('')}
                                  </AvatarFallback>
                                </Avatar>
                                <span className="font-medium dark:text-white">{student.name}</span>
                              </div>
                            </td>
                            <td className="py-3 px-4 text-gray-600 dark:text-gray-400">{student.email}</td>
                            <td className="py-3 px-4 text-center font-bold dark:text-white">{formatNumber(student.enrollment.grade)}/20</td>
                            <td className="py-3 px-4 text-center font-bold dark:text-white">{formatNumber(student.enrollment.attendance_rate)}%</td>
                            <td className="py-3 px-4 text-center">
                              <Badge className={`${getStatusColor(student.enrollment.status)} font-medium`}>
                                {getStatusLabel(student.enrollment.status)}
                              </Badge>
                            </td>
                            <td className="py-3 px-4 text-center">
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                  setSelectedStudent(student);
                                  setShowStudentModal(true);
                                }}
                              >
                                <Eye className="h-4 w-4" />
                              </Button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Autres onglets - structure de base */}
          <TabsContent value="attendance">
            <Card className="shadow-lg border-0 dark:bg-gray-800">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <UserCheck className="h-6 w-6 text-green-600 dark:text-green-400" />
                  Gestion des présences
                </CardTitle>
                <CardDescription>
                  Suivi des présences pour toutes vos classes
                </CardDescription>
              </CardHeader>
              <CardContent>
                {attendanceData && attendanceData.length > 0 ? (
                  <div className="space-y-6">
                    {attendanceData.map((classData: any) => (
                      <div
                        key={classData.id}
                        className="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:shadow-md transition-shadow"
                      >
                        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                          <div className="space-y-1">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                              <Calendar className="h-5 w-5 text-green-600 dark:text-green-400" />
                              <Link
                                href={route('training-classes.show', classData.uuid)}
                                className="hover:text-green-600 dark:hover:text-green-400 hover:underline transition-colors"
                              >
                                {classData.name}
                              </Link>
                            </h3>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                              Formation: {classData.training_title}
                            </p>
                            <div className="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                              <span className="flex items-center gap-1">
                                <Clock className="h-4 w-4" />
                                {classData.date}
                              </span>
                              <span>{classData.start_time} - {classData.end_time}</span>
                              <span className="flex items-center gap-1">
                                <MapPin className="h-4 w-4" />
                                {classData.room}
                              </span>
                            </div>
                          </div>
                          <div className="flex items-center gap-3">
                            <Badge
                              className={`${
                                classData.attendance_rate >= 80
                                  ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300'
                                  : classData.attendance_rate >= 60
                                  ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300'
                                  : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'
                              }`}
                            >
                              {classData.attendance_rate}% de présence
                            </Badge>
                          </div>
                        </div>

                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                          <div className="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Total étudiants</p>
                            <p className="text-2xl font-bold text-blue-600 dark:text-blue-400 flex items-center gap-2">
                              <Users className="h-5 w-5" />
                              {classData.total_students}
                            </p>
                          </div>

                          <div className="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Présents</p>
                            <p className="text-2xl font-bold text-green-600 dark:text-green-400 flex items-center gap-2">
                              <UserCheck className="h-5 w-5" />
                              {classData.present_count}
                            </p>
                          </div>

                          <div className="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Absents</p>
                            <p className="text-2xl font-bold text-red-600 dark:text-red-400 flex items-center gap-2">
                              <UserX className="h-5 w-5" />
                              {classData.absent_count}
                            </p>
                          </div>

                          <div className="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Excusés</p>
                            <p className="text-2xl font-bold text-yellow-600 dark:text-yellow-400 flex items-center gap-2">
                              <AlertTriangle className="h-5 w-5" />
                              {classData.excused_count}
                            </p>
                          </div>
                        </div>

                        <div className="mt-4">
                          <div className="flex items-center justify-between text-sm mb-2">
                            <span className="text-gray-600 dark:text-gray-400">Taux de présence</span>
                            <span className="font-semibold text-gray-900 dark:text-white">
                              {classData.attendance_rate}%
                            </span>
                          </div>
                          <Progress value={classData.attendance_rate} className="h-3" />
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="text-center py-12">
                    <UserCheck className="h-16 w-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                    <p className="text-gray-600 dark:text-gray-400">
                      Aucune donnée de présence disponible pour le moment
                    </p>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="evaluations">
            <Card className="shadow-lg border-0 dark:bg-gray-800">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <GraduationCap className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                  Résultats des évaluations
                </CardTitle>
                <CardDescription>
                  Vue d'ensemble des résultats de quiz pour vos formations
                </CardDescription>
              </CardHeader>
              <CardContent>
                {evaluations && evaluations.length > 0 ? (
                  <div className="space-y-6">
                    {evaluations.map((evaluation: any) => (
                      <div
                        key={evaluation.id}
                        className="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:shadow-md transition-shadow"
                      >
                        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                          <div className="space-y-1">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                              <Award className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                              {evaluation.title}
                            </h3>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                              Formation: {evaluation.training_title}
                            </p>
                          </div>
                          <div className="flex items-center gap-3">
                            <Badge
                              className={`${
                                evaluation.pass_rate >= 70
                                  ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300'
                                  : evaluation.pass_rate >= 50
                                  ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300'
                                  : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'
                              }`}
                            >
                              {evaluation.pass_rate}% de réussite
                            </Badge>
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() => window.location.href = route('trainings.quizzes.results', evaluation.uuid)}
                            >
                              <Eye className="h-4 w-4 mr-2" />
                              Détails
                            </Button>
                          </div>
                        </div>

                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                          <div className="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Tentatives</p>
                            <p className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                              {evaluation.total_attempts}
                            </p>
                          </div>

                          <div className="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Moyenne</p>
                            <p className="text-2xl font-bold text-purple-600 dark:text-purple-400">
                              {evaluation.average_score}/{evaluation.max_score}
                            </p>
                          </div>

                          <div className="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Réussis</p>
                            <p className="text-2xl font-bold text-green-600 dark:text-green-400 flex items-center gap-2">
                              <CheckCircle className="h-5 w-5" />
                              {evaluation.passed_count}
                            </p>
                          </div>

                          <div className="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Échoués</p>
                            <p className="text-2xl font-bold text-red-600 dark:text-red-400 flex items-center gap-2">
                              <XCircle className="h-5 w-5" />
                              {evaluation.failed_count}
                            </p>
                          </div>
                        </div>

                        <div className="mt-4">
                          <div className="flex items-center justify-between text-sm mb-2">
                            <span className="text-gray-600 dark:text-gray-400">Taux de réussite</span>
                            <span className="font-semibold text-gray-900 dark:text-white">
                              {evaluation.pass_rate}%
                            </span>
                          </div>
                          <Progress value={evaluation.pass_rate} className="h-3" />
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="text-center py-12">
                    <GraduationCap className="h-16 w-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                    <p className="text-gray-600 dark:text-gray-400">
                      Aucune évaluation disponible pour le moment
                    </p>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>

        {/* Modal détail étudiant */}
        <Dialog open={showStudentModal} onOpenChange={setShowStudentModal}>
          <DialogContent className="max-w-2xl dark:bg-gray-800">
            <DialogHeader>
              <DialogTitle className="flex items-center gap-3">
                {selectedStudent && (
                  <>
                    <Avatar>
                      <AvatarImage
                        src={selectedStudent.avatar ? `/storage/${selectedStudent.avatar}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(selectedStudent.name)}`}
                        alt={selectedStudent.name}
                      />
                      <AvatarFallback className="bg-primary text-white">
                        {selectedStudent.name.split(' ').map(n => n[0]).join('')}
                      </AvatarFallback>
                    </Avatar>
                    <div>
                      <div className="text-xl font-bold dark:text-white">{selectedStudent.name}</div>
                      <div className="text-sm text-gray-600 dark:text-gray-400">{selectedStudent.email}</div>
                    </div>
                  </>
                )}
              </DialogTitle>
            </DialogHeader>

            {selectedStudent && (
              <div className="space-y-6">
                <div className="grid grid-cols-3 gap-4">
                  <div className="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div className="text-2xl font-bold text-primary dark:text-blue-400">{formatNumber(selectedStudent.enrollment.grade)}/20</div>
                    <div className="text-sm text-gray-600 dark:text-gray-400">Note moyenne</div>
                  </div>
                  <div className="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div className="text-2xl font-bold text-green-600 dark:text-green-400">{formatNumber(selectedStudent.enrollment.attendance_rate)}%</div>
                    <div className="text-sm text-gray-600 dark:text-gray-400">Assiduité</div>
                  </div>
                  <div className="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">{formatNumber(selectedStudent.enrollment.progress)}%</div>
                    <div className="text-sm text-gray-600 dark:text-gray-400">Progression</div>
                  </div>
                </div>
              </div>
            )}

            <DialogFooter>
              <Button variant="outline" onClick={() => setShowStudentModal(false)}>
                Fermer
              </Button>
              <Button>
                <MessageSquare className="h-4 w-4 mr-2" />
                Contacter
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </DashboardLayout>
  );
};

export default TeacherDashboard;
