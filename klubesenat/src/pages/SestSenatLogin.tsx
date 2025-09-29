import { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { useAuth } from "@/hooks/useAuth";
import { AlertCircle, ArrowLeft, User, Lock, CheckCircle } from "lucide-react";
import { useNavigate } from "react-router-dom";

const SestSenatLogin = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [localError, setLocalError] = useState('');
  const { login, isLoading, error, user, isAuthenticated } = useAuth();
  const navigate = useNavigate();

  // Redirect if already authenticated
  useEffect(() => {
    if (isAuthenticated && user) {
      console.log('SestSenat: Usuário já autenticado, redirecionando para dashboard');
      navigate('/', { replace: true });
    }
  }, [isAuthenticated, user, navigate]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLocalError('');

    if (!email || !password) {
      setLocalError('Por favor, preencha todos os campos.');
      return;
    }

    try {
      console.log('SestSenatLogin: Iniciando login para:', email);
      await login(email, password);
      console.log('SestSenatLogin: Login bem-sucedido, redirecionando...');
      navigate('/', { replace: true });
    } catch (error) {
      console.error('SestSenatLogin: Erro no login:', error);
      const errorMessage = error instanceof Error ? error.message : 'Erro desconhecido';
      setLocalError(errorMessage);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-blue-100 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        {/* Logo e Header */}
        <div className="text-center mb-8">
          <div className="mx-auto w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center mb-4">
            <div className="text-white font-bold text-2xl">SS</div>
          </div>
          <h1 className="text-3xl font-bold text-gray-900 mb-2">SestSenat Portal</h1>
          <p className="text-gray-600">Portal exclusivo para usuários do Senat</p>
        </div>

        <Card className="shadow-xl">
          <CardHeader className="space-y-1">
            <CardTitle className="text-2xl text-center">Entrar</CardTitle>
            <CardDescription className="text-center">
              Use suas credenciais do Klube Cash para acessar
            </CardDescription>
          </CardHeader>
          <CardContent>
            {(error || localError) && (
              <Alert className="mb-4 border-red-200 bg-red-50">
                <AlertCircle className="h-4 w-4 text-red-600" />
                <AlertDescription className="text-red-800">
                  {localError || error}
                </AlertDescription>
              </Alert>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="email">E-mail</Label>
                <div className="relative">
                  <User className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                  <Input
                    id="email"
                    type="email"
                    placeholder="Digite seu e-mail"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="pl-10"
                    required
                    autoComplete="email"
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">Senha</Label>
                <div className="relative">
                  <Lock className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                  <Input
                    id="password"
                    type="password"
                    placeholder="Digite sua senha"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    className="pl-10"
                    required
                    autoComplete="current-password"
                  />
                </div>
              </div>

              <Button type="submit" className="w-full bg-blue-600 hover:bg-blue-700" disabled={isLoading}>
                {isLoading ? 'Entrando...' : 'Entrar'}
              </Button>
            </form>

            <div className="mt-6 space-y-4">
              <div className="relative">
                <div className="absolute inset-0 flex items-center">
                  <span className="w-full border-t" />
                </div>
                <div className="relative flex justify-center text-xs uppercase">
                  <span className="bg-white px-2 text-gray-500">ou</span>
                </div>
              </div>

              <Button
                type="button"
                variant="outline"
                className="w-full"
                onClick={() => window.location.href = 'https://klubecash.com/login'}
              >
                <ArrowLeft className="mr-2 h-4 w-4" />
                Voltar para o Login Principal
              </Button>
            </div>

            <div className="mt-6 text-center text-sm text-gray-500">
              <p>
                Ainda não tem conta?{' '}
                <a href="https://klubecash.com/cadastro" className="text-blue-600 hover:text-blue-700 font-medium">
                  Cadastre-se no Klube Cash
                </a>
              </p>
            </div>
          </CardContent>
        </Card>

        <div className="mt-6 text-center text-xs text-gray-500">
          <p>Portal exclusivo para funcionários e membros do Senat</p>
          <p>Acesso restrito conforme aprovação no sistema principal</p>
        </div>
      </div>
    </div>
  );
};

export default SestSenatLogin;