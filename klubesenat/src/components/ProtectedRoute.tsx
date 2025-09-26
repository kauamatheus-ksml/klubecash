import { ReactNode, useEffect, useState } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { Navigate, useSearchParams } from 'react-router-dom';
import { Loader2 } from 'lucide-react';

interface ProtectedRouteProps {
  children: ReactNode;
}

const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ children }) => {
  const { user, verifySession, isLoading } = useAuth();
  const [searchParams] = useSearchParams();
  const [isChecking, setIsChecking] = useState(true);

  useEffect(() => {
    const checkAuth = async () => {
      if (user) {
        setIsChecking(false);
        return;
      }

      // Verificar se há parâmetros de sessão vindos do Klube Cash
      const sessionId = searchParams.get('session');
      const email = searchParams.get('email');
      const senat = searchParams.get('senat');

      if (sessionId && email && senat === 'Sim') {
        try {
          const verifiedUser = await verifySession(sessionId, email);
          if (verifiedUser) {
            setIsChecking(false);
            return;
          }
        } catch (error) {
          console.error('Erro ao verificar sessão:', error);
        }
      }

      setIsChecking(false);
    };

    checkAuth();
  }, [user, verifySession, searchParams]);

  if (isChecking || isLoading) {
    const hasSessionParams = searchParams.get('session') && searchParams.get('email');

    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
        <div className="text-center space-y-6 max-w-md px-4">
          <div className="mx-auto w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center">
            <Loader2 className="h-8 w-8 text-white animate-spin" />
          </div>
          <div className="space-y-2">
            <h2 className="text-2xl font-bold text-gray-900">SestSenat Portal</h2>
            <p className="text-gray-600">
              {hasSessionParams
                ? 'Verificando seu acesso do Klube Cash...'
                : 'Verificando autenticação...'}
            </p>
          </div>
          {hasSessionParams && (
            <div className="text-sm text-gray-500">
              Aguarde enquanto validamos sua sessão
            </div>
          )}
        </div>
      </div>
    );
  }

  if (!user || user.senat !== 'Sim') {
    return <Navigate to="/login" replace />;
  }

  return <>{children}</>;
};

export default ProtectedRoute;