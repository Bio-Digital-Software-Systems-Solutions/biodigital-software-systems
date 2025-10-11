import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { Project, ProjectStatus } from '@/Types/Project';
import { apiLogger } from '@/utils/logger';

interface UseProjectsOptions {
  status?: ProjectStatus;
}

export const useProjects = (options?: UseProjectsOptions) => {
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchProjects = useCallback(async () => {
    try {
      setLoading(true);
      const response = await axios.get('/api/projects', {
        params: options,
      });
      setProjects(response.data.data || response.data);
      setError(null);
    } catch (err) {
      setError('Erreur lors du chargement des projets');
      apiLogger.error('Error fetching projects', err);
    } finally {
      setLoading(false);
    }
  }, [options]);

  const createProject = useCallback(async (data: Partial<Project>) => {
    try {
      const response = await axios.post('/api/projects', data);
      const newProject = response.data;
      setProjects(prev => [newProject, ...prev]);
      return newProject;
    } catch (error) {
      apiLogger.error('Error creating project', error);
      throw error;
    }
  }, []);

  const updateProject = useCallback(async (id: number, data: Partial<Project>) => {
    try {
      const response = await axios.patch(`/api/projects/${id}`, data);
      const updatedProject = response.data;
      setProjects(prev => prev.map(p => p.id === id ? updatedProject : p));
      return updatedProject;
    } catch (error) {
      apiLogger.error('Error updating project', error);
      throw error;
    }
  }, []);

  const deleteProject = useCallback(async (id: number) => {
    try {
      await axios.delete(`/api/projects/${id}`);
      setProjects(prev => prev.filter(p => p.id !== id));
    } catch (error) {
      apiLogger.error('Error deleting project', error);
      throw error;
    }
  }, []);

  useEffect(() => {
    fetchProjects();
  }, [fetchProjects]);

  return {
    projects,
    loading,
    error,
    createProject,
    updateProject,
    deleteProject,
    refetch: fetchProjects,
  };
};
