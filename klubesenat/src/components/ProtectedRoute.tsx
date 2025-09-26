import { ReactNode, useEffect, useState } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { Loader2 } from 'lucide-react';

interface ProtectedRouteProps {
  children: ReactNode;
}

const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ children }) => {
  const { user, isLoading } = useAuth();
  const [isInitializing, setIsInitializing] = useState(true);

  useEffect(() => {
    // Dar tempo para o useAuth processar o localStorage
    const timer = setTimeout(() => {
      setIsInitializing(false);
    }, 100);

    return () => clearTimeout(timer);
  }, []);

  if (isInitializing || isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
        <div className="text-center space-y-6 max-w-md px-4">
          <div className="mx-auto w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center">
            <Loader2 className="h-8 w-8 text-white animate-spin" />
          </div>
          <div className="space-y-2">
            <h2 className="text-2xl font-bold text-gray-900">SestSenat Portal</h2>
            <p className="text-gray-600">Verificando autenticação...</p>
          </div>
          <div className="text-sm text-gray-500">
            Carregando dados da sua sessão
          </div>
        </div>
      </div>
    );
  }

  if (!user || user.senat !== 'Sim') {
    window.location.href = 'https://klubecash.com/cliente/escolher-carteira';
    return null;
  }

  return <>{children}</>;
};

export default ProtectedRoute;