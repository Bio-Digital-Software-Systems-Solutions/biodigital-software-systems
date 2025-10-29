import { User } from './index';

export enum ProjectStatus {
  PLANNING = 'planning',
  ACTIVE = 'active',
  ON_HOLD = 'on_hold',
  COMPLETED = 'completed',
  CANCELLED = 'cancelled',
}

export enum TaskStatus {
  PENDING = 'pending',
  TODO = 'todo',
  IN_PROGRESS = 'in_progress',
  UNDER_REVIEW = 'under_review',
  IN_REVIEW = 'in_review', // Alias for backward compatibility
  BLOCKED = 'blocked',
  ON_HOLD = 'on_hold',
  COMPLETED = 'completed',
  DONE = 'done', // Alias for backward compatibility
  CANCELLED = 'cancelled',
}

export interface StatusObject {
  id: number;
  uuid: string;
  name: string;
  description?: string;
  color: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export enum Priority {
  LOWEST = 'lowest',
  LOW = 'low',
  MEDIUM = 'medium',
  HIGH = 'high',
  HIGHEST = 'highest',
}

export enum TaskType {
  TASK = 'task',
  BUG = 'bug',
  FEATURE = 'feature',
  STORY = 'story',
  EPIC = 'epic',
  SUBTASK = 'subtask',
}

export interface Project {
  id: number;
  uuid: string;
  name: string;
  slug: string;
  description?: string;
  status: ProjectStatus;
  priority: Priority;
  color: string;
  start_date?: string;
  end_date?: string;
  budget?: number;
  project_manager_id?: number;
  reviewer_id?: number;
  is_template: boolean;
  settings?: Record<string, any>;
  progress: number;
  manager?: User;
  reviewer?: User;
  members?: ProjectMember[];
  participants?: ProjectParticipant[];
  comments?: ProjectComment[];
  attachments?: ProjectAttachment[];
  tasks?: ProjectTask[];
  tasks_count?: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
}

export interface ProjectTask {
  id: number;
  uuid: string;
  title: string;
  key: string;
  description?: string;
  project_id: number;
  project?: Project;
  parent_id?: number;
  parent?: ProjectTask;
  children?: ProjectTask[];
  assignee_id?: number;
  assignee?: User;
  reporter_id: number;
  reporter: User;
  reviewer_id?: number;
  reviewer?: User;
  status?: StatusObject;
  status_id?: number;
  priority: Priority;
  type: TaskType;
  story_points?: number;
  estimated_hours?: number;
  due_date?: string;
  sprint_id?: number;
  sprint?: Sprint;
  epic_id?: number;
  epic?: ProjectTask;
  labels: string[];
  custom_fields?: Record<string, any>;
  position: number;
  is_overdue?: boolean;
  is_blocked?: boolean;
  reviewed?: boolean;
  reviewed_at?: string;
  started_at?: string;
  paused_at?: string;
  stopped_at?: string;
  participants?: User[];
  comments?: TaskComment[];
  attachments?: TaskAttachment[];
  created_at: string;
  updated_at: string;
  deleted_at?: string;
}

export interface Sprint {
  id: number;
  name: string;
  goal?: string;
  project_id: number;
  project?: Project;
  start_date: string;
  end_date: string;
  status: 'planned' | 'active' | 'completed' | 'cancelled';
  capacity?: number;
  tasks?: ProjectTask[];
  velocity?: number;
  created_at: string;
  updated_at: string;
}

export interface ProjectMember {
  id?: number;
  project_id: number;
  user_id: number;
  user: User;
  project_role_id?: number;
  hourly_rate?: number;
  availability_percentage: number;
  started_at: string;
  ended_at?: string;
  is_lead: boolean;
}

export interface KanbanColumn {
  status: TaskStatus;
  label: string;
  color: string;
  tasks?: ProjectTask[];
}

export interface TaskFilters {
  project_id?: number;
  status?: TaskStatus;
  assignee_id?: number;
  sprint_id?: number;
  type?: TaskType;
  priority?: Priority;
}

export interface TaskAttachment {
  id: number;
  task_id: number;
  user_id: number;
  user?: User;
  file_name: string;
  file_path: string;
  file_type: 'image' | 'video' | 'document';
  mime_type: string;
  file_size: number;
  file_url: string;
  formatted_file_size?: string;
  created_at: string;
  updated_at: string;
}

export interface TaskComment {
  id: number;
  task_id: number;
  user_id: number;
  user: User;
  content: string;
  created_at: string;
  updated_at: string;
}

export interface TaskParticipant {
  id: number;
  task_id: number;
  user_id: number;
  user: User;
  role: string;
  created_at: string;
  updated_at: string;
}

export interface ProjectParticipant {
  id: number;
  project_id: number;
  user_id: number;
  user: User;
  role: 'member' | 'contributor' | 'observer';
  created_at: string;
  updated_at: string;
}

export interface ProjectComment {
  id: number;
  project_id: number;
  user_id: number;
  parent_id?: number;
  user: User;
  content: string;
  replies?: ProjectComment[];
  created_at: string;
  updated_at: string;
}

export interface ProjectAttachment {
  id: number;
  project_id: number;
  user_id: number;
  user?: User;
  file_name: string;
  file_path: string;
  file_type: 'image' | 'video' | 'document';
  mime_type: string;
  file_size: number;
  file_url: string;
  formatted_file_size?: string;
  created_at: string;
  updated_at: string;
}
