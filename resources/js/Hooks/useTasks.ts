import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { ProjectTask, TaskStatus, TaskFilters } from '@/Types/Project';
import { apiLogger } from '@/utils/logger';

export const useTasks = (projectId?: string | number, filters?: TaskFilters) => {
  const [tasks, setTasks] = useState<ProjectTask[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchTasks = useCallback(async () => {
    try {
      setLoading(true);
      const params = {
        ...filters,
        project_id: projectId,
      };
      const response = await axios.get('/api/tasks', { params });
      setTasks(response.data);
      setError(null);
    } catch (err) {
      setError('Erreur lors du chargement des tâches');
      apiLogger.error('Error fetching tasks', err);
    } finally {
      setLoading(false);
    }
  }, [projectId, filters]);

  const createTask = useCallback(async (data: Partial<ProjectTask>) => {
    try {
      const response = await axios.post('/api/tasks', {
        ...data,
        project_id: projectId || data.project_id,
      });
      const newTask = response.data;
      setTasks(prev => [newTask, ...prev]);
      return newTask;
    } catch (error) {
      apiLogger.error('Error creating task', error);
      throw error;
    }
  }, [projectId]);

  const updateTask = useCallback(async (taskId: number, data: Partial<ProjectTask>) => {
    try {
      const response = await axios.patch(`/api/tasks/${taskId}`, data);
      const updatedTask = response.data;
      setTasks(prev => prev.map(t => t.id === taskId ? updatedTask : t));
      return updatedTask;
    } catch (error) {
      apiLogger.error('Error updating task', error);
      throw error;
    }
  }, []);

  const updateTaskStatus = useCallback(async (taskId: number, status: TaskStatus) => {
    try {
      const response = await axios.patch(`/api/tasks/${taskId}/status`, { status });
      const updatedTask = response.data;
      setTasks(prev => prev.map(t => t.id === taskId ? updatedTask : t));
      return updatedTask;
    } catch (error) {
      apiLogger.error('Error updating task status', error);
      throw error;
    }
  }, []);

  const deleteTask = useCallback(async (taskId: number) => {
    try {
      await axios.delete(`/api/tasks/${taskId}`);
      setTasks(prev => prev.filter(t => t.id !== taskId));
    } catch (error) {
      apiLogger.error('Error deleting task', error);
      throw error;
    }
  }, []);

  useEffect(() => {
    if (projectId) {
      fetchTasks();
    }
  }, [fetchTasks]);

  return {
    tasks,
    loading,
    error,
    createTask,
    updateTask,
    updateTaskStatus,
    deleteTask,
    refetch: fetchTasks,
  };
};
